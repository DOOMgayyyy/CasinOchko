import React, { useContext, useEffect, useState, useMemo, useRef } from 'react';
import CONFIG from '../../config';
import Button from '../../components/Button/Button';
import { IBasePage, PAGES } from '../PageManager';
import Game from '../../game/Game';
import { ServerContext, StoreContext } from '../../App';
import { TPlayer, TRoomInfoResponse } from '../../services/server/types';

import tableImgSrc from '../../assets/img/Table/Table.png';
import chatIcon from '../../assets/img/chat_bubble.svg';
import './Game.scss';
import Chat from '../Chat/Chat';
import SnowEffect from '../../components/SnowEffect/SnowEffect';
import GameResultModal, { GameResultStatus } from '../../components/GameResultModal/GameResultModal';
import useGetCardImage from './hooks/useGetCardImage';


const GAME_FIELD = 'game-field';

const GamePage: React.FC<IBasePage> = (props: IBasePage) => {
    const { setPage } = props;
    const server = useContext(ServerContext);
    const store = useContext(StoreContext);
    
    let game: Game | null = null;
    let interval: NodeJS.Timeout | null = null;

    // Состояние игры из сервера
    const [myCards, setMyCards] = useState<string>('');
    const [myScore, setMyScore] = useState<number>(0);
    const [dealerCards, setDealerCards] = useState<string>('');
    const [dealerScore, setDealerScore] = useState<number>(0);
    const [players, setPlayers] = useState<TPlayer[]>([]);
    const [playersScores, setPlayersScores] = useState<{ [memberId: number]: number }>({});
    const [timer, setTimer] = useState<number | null>(null);
    const [currentPlayerId, setCurrentPlayerId] = useState<number | null>(null);
    const [myMemberId, setMyMemberId] = useState<number | null>(null);
    const [roomCode, setRoomCode] = useState<string | null>(null);
    const [copySuccess, setCopySuccess] = useState(false);
    const [showLeaveModal, setShowLeaveModal] = useState(false);
    const [showChat, setShowChat] = useState(false);
    const [showBetModal, setShowBetModal] = useState(false);
    const [currentBet, setCurrentBet] = useState(0);
    const betInputRef = useRef<HTMLInputElement>(null);
    const [showResultModal, setShowResultModal] = useState(false);
    const [gameResult, setGameResult] = useState<{
        status: GameResultStatus;
        betAmount: number;
    } | null>(null);
    const lastResultShown = useRef<string | null>(null);
    //фаза игры
    const [gamePhase, setGamePhase] = useState<string>('');
    
    // Мемоизированная функция для получения изображений карт
    const getCardImage = useMemo(useGetCardImage, []);
    
    // Получаем roomId из store
    const roomId = store.getCurrentRoomId();
    const user = store.getUser();

    const handleHit = async () => {
        if (!roomId || !user) {
          return;
        }

        const result = await server.takeUserCard(roomId);
        
        if (result && result.success) {
          if (result.shouldPass) {
            // Автоматический пас при переборе или 5 картах
          }
        }
      };
    
      const handleStand = async () => {
        if (!roomId || !user) {
          return;
        }

        const result = await server.pass(roomId);
      };
    
      const handleDouble = async () => {
        if (!roomId || !user) {
          return;
        }

        const result = await server.doubleBet(roomId);
      };
    
      const handleBet = () => {
        setCurrentBet(0);
        setShowBetModal(true);
      };

      const MIN_BET = 100

      const handleBetIncrease = () => {
        if (user) {
          const newBet = Math.min(currentBet + 50, user.balance);
          setCurrentBet(newBet);
        }
      };

      const handleBetDecrease = () => {
        const newBet = Math.max(currentBet - 50, 100);
        setCurrentBet(newBet);
      };

      const handleBetInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value;
        // Только числа или пустая строка
        if (value === '' || /^\d+$/.test(value)) {
          if (value === '') {
            setCurrentBet(0);
            return;
          }
          
          const numValue = parseInt(value, 10);
          if (user && numValue > user.balance) {
            setCurrentBet(user.balance);
          } else if (numValue < 0) {
            setCurrentBet(0);
          } else {
            setCurrentBet(numValue);
          }
        }
      };


      const handlePlaceBet = async () => {
        if (!roomId || currentBet === 0 || !user) {
          return;
        }

        const result = await server.makeBet(roomId, currentBet);
        
        if (result && result.success) {
          setShowBetModal(false);
          setCurrentBet(0);
        }
      };

      const handleCancelBet = () => {
        setShowBetModal(false);
        setCurrentBet(0);
      };
    
      const handleBackToLobby = () => {
        setShowLeaveModal(true);
      };

      const handleLeaveRoom = async () => {
        // Останавливаем game loop при выходе
        server.stopGameLoop();
        const result = await server.leaveRoom();
        if (result) {
          store.clearCurrentRoomId();
          store.clearRoomCode();
          setPage(PAGES.LOBBY);
        }
        setShowLeaveModal(false);
      };

      const handleStayInRoom = () => {
        setShowLeaveModal(false);
      };
        
      const handleChatToggle = () => {
        setShowChat(!showChat);
        // Логика для открытия/закрытия чата
      };

      const handleCloseResultModal = () => {
        setShowResultModal(false);
        setGameResult(null);
      };

    useEffect(() => {
        // инициализация игры
        game = new Game();
        return () => {
            // деинициализировать все экземпляры
            game?.destructor();
            game = null;
            if (interval) {
                clearInterval(interval);
                interval = null;
            }
        }
    }, []);


    useEffect(() => {
        const code = store.getRoomCode();
        if (code) {
            setRoomCode(code);
            store.clearRoomCode();
        }
    }, [store]);

    const handleCopyCode = async () => {
        if (roomCode) {
            await navigator.clipboard.writeText(roomCode);
            setCopySuccess(true);
            setTimeout(() => setCopySuccess(false), 2000);
        }
    };

    // Функция для отображения названий фаз игры
    const getPhaseDisplayName = (phase: string): string => {
        const phaseMap: Record<string, string> = {
            'waiting': 'Ожидание ставок',
            'waiting_for_bets': 'Фаза ставок',
            'playing': 'Игра',
            'player_turn': 'Ход игроков',
            'dealer_turn': 'Ход дилера',
            'show_results': 'Игра завершена',
        };
        return phaseMap[phase] || phase;
    };

    // Определяем статус текущего игрока
    const myPlayer = useMemo(() => {
        if (!user || myMemberId === null) return null;
        return players.find(p => p.memberId === myMemberId);
    }, [players, myMemberId, user]);

    const isSpectator = myPlayer?.status === 'spectator';

    // Game loop - каждую секунду запрашиваем обновления состояния игры
    useEffect(() => {
        if (!roomId) {
            // Если нет roomId, возвращаемся в лобби
            setPage(PAGES.LOBBY);
            return;
        }

        // Функция обработки обновлений игры
        const handleGameUpdate = async (roomInfo: TRoomInfoResponse) => {
            // Всегда обновляем таймер (он всегда присутствует)
            setTimer(roomInfo.timer);
            
            // Обновляем фазу игры, если она пришла
            if (roomInfo.status !== undefined) {
                setGamePhase(roomInfo.status);
            }

            // Обновляем остальные данные только если они пришли (при полном обновлении)
            if (roomInfo.myCards !== undefined) {
                setMyCards(roomInfo.myCards);
                // Вычисляем счёт моих карт через сервер
                if (roomInfo.myCards && roomInfo.myCards.length > 0) {
                    server.calculateUserScore(roomInfo.myCards).then(score => {
                        if (score !== null) {
                            setMyScore(score);
                        }
                    });
                } else {
                    setMyScore(0);
                }
            }
            
            // Обновляем карты дилера
            if (roomInfo.dealerCards !== undefined) {
                setDealerCards(roomInfo.dealerCards);
                // Вычисляем счёт карт дилера через сервер
                if (roomInfo.dealerCards && roomInfo.dealerCards.length > 0) {
                    server.calculateUserScore(roomInfo.dealerCards).then(score => {
                        if (score !== null) {
                            setDealerScore(score);
                        }
                    });
                } else {
                    setDealerScore(0);
                }
            }
            
            if (roomInfo.players !== undefined) {
                setPlayers(roomInfo.players);
                // Вычисляем счёт для каждого игрока через сервер
                const scores: { [memberId: number]: number } = {};
                for (const player of roomInfo.players) {
                    if (player.cards && player.cards.length > 0) {
                        server.calculateUserScore(player.cards).then(score => {
                            if (score !== null) {
                                scores[player.memberId] = score;
                                setPlayersScores({...scores});
                            }
                        });
                    } else {
                        scores[player.memberId] = 0;
                    }
                }
                setPlayersScores(scores);
            }
            
            if (roomInfo.currentPlayerId !== undefined) setCurrentPlayerId(roomInfo.currentPlayerId);
            
            // Находим себя в списке игроков
            if (user && roomInfo.players) {
                const myPlayer = roomInfo.players.find(p => p.userId == user.id);
                if (myPlayer) {
                    setMyMemberId(myPlayer.memberId);
                    
                    // Обновляем баланс пользователя из данных игрока
                    if (myPlayer.balance !== user.balance) {
                        store.setUser({ ...user, balance: myPlayer.balance });
                    }
                    
                    // Проверяем статус игрока на результат игры
                    const validResultStatuses: GameResultStatus[] = ['bust', 'push', 'blackjack', 'win', 'lose'];
                    
                    if (validResultStatuses.includes(myPlayer.status as GameResultStatus)) {
                        // Создаём уникальный ключ для результата (чтобы не показывать один и тот же результат повторно)
                        const resultKey = `${myPlayer.memberId}-${myPlayer.status}-${myPlayer.bet}`;
                        
                        if (lastResultShown.current !== resultKey) {
                            setGameResult({
                                status: myPlayer.status as GameResultStatus,
                                betAmount: myPlayer.bet
                            });
                            setShowResultModal(true);
                            lastResultShown.current = resultKey;
                        }
                    } else {
                        // Если статус не результат (например, spectator или player), сбрасываем отслеживание
                        lastResultShown.current = null;
                    }
                }
            }
        };

        // Загрузка начальных данных комнаты
        const loadInitialData = async () => {
            const initialData = await server.getInfoRoom(roomId);
            if (initialData) {
                handleGameUpdate(initialData);
            }
        };
        loadInitialData();

        // Запускаем game loop
        server.startGameLoop(roomId, handleGameUpdate);

        // Очистка при размонтировании компонента
        return () => {
            server.stopGameLoop();
        };
    }, [roomId, server, user, setPage]);

    return (<div className='game-page'>
        <SnowEffect />
        <div className="game-scale-wrapper">
           
        <div className="game-table-container">
            <img src={tableImgSrc} alt="Poker Table" className="game-table-image" />
        </div>
        
        {/* надписи с инфой игроков за столом */}
        <div id={GAME_FIELD} className={GAME_FIELD}>
            <div className="players">
                {players.map((player, index) => {
                    const positions = ['left-top', 'left-middle', 'left-bottom', 'right-bottom', 'right-middle', 'right-top'];
                    const position = positions[index] || 'left-top';
                    const isCurrentPlayer = myMemberId !== null && player.memberId === myMemberId;
                    const isActiveTurn = currentPlayerId === player.memberId;
                    const score = playersScores[player.memberId] ?? 0;
                    
                    return (
                        <div 
                            className={`player-slot ${position} ${isCurrentPlayer ? 'active-player' : ''} ${isActiveTurn ? 'current-turn' : ''}`} 
                            key={player.memberId}
                        >
                            <span className="name">{player.name}</span>
                            <span className="balance">${player.balance}</span>
                            <span className="score">{score}</span>
                            {player.bet > 0 && <span className="bet">Ставка: ${player.bet}</span>}
                        </div>
                    );
                })}
            </div>

            {/* карты игроков на столе */}
            <div className="table-cards-container">
                {players.map((player, index) => {
                    const positions = ['left-top', 'left-middle', 'left-bottom', 'right-bottom', 'right-middle', 'right-top'];
                    const position = positions[index] || 'left-top';
                    const playerCards = player.cards ? player.cards.match(/.{1,2}/g) : [];
                    
                    return (
                        <div 
                            className={`table-player-cards ${position}`} 
                            key={`table-cards-${player.memberId}`}
                        >
                            {playerCards && playerCards.length > 0 ? (
                                playerCards.map((card, cardIndex) => {
                                    const cardImage = getCardImage(card);
                                    return (
                                        <div 
                                            className="table-card" 
                                            key={cardIndex}
                                            style={{ animationDelay: `${cardIndex * 0.2}s` }}
                                        >
                                            {cardImage ? (
                                                <img src={cardImage} alt={card} />
                                            ) : (
                                                <span>{card}</span>
                                            )}
                                        </div>
                                    );
                                })
                            ) : null}
                        </div>
                    );
                })}
            </div>
             {/* дилер */}
             <div className='diller-slot'>
                <span className="diller-name">Дилер: </span>
                {dealerCards && dealerCards.length > 0 && (
                    <span className="diller-score">{dealerScore}</span>
                )}
            </div>
            
            {/* карты дилера */}
            <div className="dealer-cards">
                {dealerCards && dealerCards.length > 0 ? (
                    dealerCards.match(/.{1,2}/g)?.map((card, index) => {
                        const cardImage = getCardImage(card);
                        return (
                            <div 
                                className="dealer-card" 
                                key={index}
                                style={{ animationDelay: `${index * 0.2}s` }}
                            >
                                {cardImage ? (
                                    <img src={cardImage} alt={card} />
                                ) : (
                                    <span>{card}</span>
                                )}
                            </div>
                        );
                    })
                ) : null}
            </div>
            
            {/* лого снизу */}
            <div className="game-brand-logo" aria-label="CASINOCHKO">
                <span className="brand-white">CASIN</span>
                <span className="brand-yellow">OCHKO</span>
            </div>

            {/* карты справа */}
            {!isSpectator && (
                <>
                    <div className="player-cards">
                        <span className="your-cards-label">Ваши карты:</span>
                    </div>
                    <div className="my-cards">
                        {myCards && myCards.length > 0 ? (
                            // Преобразуем строку карт в массив по 2 символа
                            myCards.match(/.{1,2}/g)?.map((card, index) => {
                                const cardImage = getCardImage(card);
                                return (
                                    <div className="card" key={index}>
                                        {cardImage ? (
                                            <img src={cardImage} alt={card} />
                                        ) : (
                                            <span>{card}</span>
                                        )}
                                    </div>
                                );
                            })
                        ) : (
                            <div className="card">
                                <span>Нет карт</span>
                            </div>
                        )}
                    </div>
                </>
            )}

        <div className='timer-div'>
            {/* Фаза игры */}
            {gamePhase && (
                <div className="game-phase">
                    {getPhaseDisplayName(gamePhase)}
                </div>
            )}
            <span className='timer-span'>Таймер хода:</span>
            <span className='timer-count'>
                ⏱ {timer !== null ? timer : '—'}
            </span>
        </div>

        {!isSpectator && (
            <div className='game-controls vertical'>
                <button className="game-button double-button" onClick={handleDouble}>
                    Удвоить
                </button>
                <button className="game-button hit-button" onClick={handleHit}>
                    Взять карту
                </button>
                <button className="game-button stand-button" onClick={handleStand}>
                    Отказаться
                </button>
            </div>
        )}
        <button className="game-button bet-button" onClick={handleBet}>
            Ставка
        </button>

        {!isSpectator && (
            <div className='count-div'>
                <span className='count-span'>Ваши очки:</span>
                <span className='count-number'>{myScore}</span>
            </div>
        )}

        <button className="back-to-lobby-button" onClick={handleBackToLobby} />
            <div className="top-right-controls">
            <span className="room-id">
                Комната {roomId ? `#${roomId}` : '—'}
            </span>
                <button className="chat-button" onClick={handleChatToggle}>
                    <img src={chatIcon} alt="Chat" className="chat-icon" />
                </button>
            </div>
        </div>
        </div>

        {roomCode && (
            <div className="room-created-message">
                <div className="room-created-header">
                    <span className="success-icon">✅</span>
                    <span className="success-text">Комната создана!</span>
                    <button className="close-message-btn" onClick={() => setRoomCode(null)}>×</button>
                </div>
                <div className="room-code-display">
                    <div className="room-code-label">Код комнаты:</div>
                    <div 
                        className="room-code-value clickable" 
                        onClick={handleCopyCode}
                        title="Нажмите, чтобы скопировать код">
                        {roomCode}
                    </div>
                </div>
                {copySuccess && (
                    <div className="copy-success">Код скопирован!</div>
                )}
            </div>
        )}

        {showLeaveModal && (
            <div className="leave-room-modal-overlay" onClick={handleStayInRoom}>
                <div className="leave-room-modal" onClick={(e) => e.stopPropagation()}>
                    <div className="leave-room-modal-content">
                        <h2 className="leave-room-modal-title">Покинуть комнату?</h2>
                        <div className="leave-room-modal-buttons">
                            <button 
                                className="leave-room-button leave-button" 
                                onClick={handleLeaveRoom}
                            >
                                Покинуть
                            </button>
                            <button 
                                className="leave-room-button stay-button" 
                                onClick={handleStayInRoom}
                            >
                                Остаться
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        )}
        <Chat 
            isOpen={showChat} 
            onClose={() => setShowChat(false)}
            roomId={roomId} // roomId уже объявлен в вашем компоненте
        />

        {showBetModal && (
            <div className="bet-modal-overlay" onClick={handleCancelBet}>
                <div className="bet-modal" onClick={(e) => e.stopPropagation()}>
                    <div className="bet-modal-content">
                        <h2 className="bet-modal-title">Сделать ставку</h2>
                        
                        <div className="bet-balance">
                            <span className="bet-balance-label">Ваш баланс:</span>
                            <span className="bet-balance-amount">${user?.balance || 0}</span>
                        </div>

                        <div className="bet-controls">
                            <button 
                                className="bet-control-button bet-decrease" 
                                onClick={handleBetDecrease}
                                disabled={currentBet === 100}
                            >
                                - 50$
                            </button>
                            <input
                                ref={betInputRef}
                                type="text"
                                className="bet-amount-input"
                                value={currentBet === 0 ? '' : currentBet}
                                onChange={handleBetInputChange}
                                placeholder="0"
                                maxLength={10}
                            />
                            <button 
                                className="bet-control-button bet-increase" 
                                onClick={handleBetIncrease}
                                disabled={!user || currentBet + 50 > (user.balance || 0)}
                            >
                                + 50$
                            </button>
                        </div>

                        <div className="bet-modal-buttons">
                            <button 
                                className="bet-action-button bet-cancel" 
                                onClick={handleCancelBet}
                            >
                                <span className="bet-arrow">&lt;</span> отмена
                            </button>
                            <button 
                                className="bet-action-button bet-place" 
                                onClick={handlePlaceBet}
                                disabled={currentBet < MIN_BET || !user || currentBet > (user.balance || 0)}
                            >
                                поставить <span className="bet-arrow">&gt;</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        )}

        {showResultModal && gameResult && (
            <GameResultModal
                status={gameResult.status}
                betAmount={gameResult.betAmount}
                onClose={handleCloseResultModal}
            />
        )}
    </div>)
}

export default GamePage;