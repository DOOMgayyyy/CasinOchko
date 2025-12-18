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
import PlayerGameControls from '../../components/PlayerGameControls/PlayerGameControls';
import LeaveRoomModal from '../../components/LeaveRoomModal/LeaveRoomModal';
import BetModal from '../../components/BetModal/BetModal';
import RoomCodeMessage from '../../components/RoomCodeMessage/RoomCodeMessage';
import GameTable from '../../components/GameTable/GameTable';
import DealerInfo from '../../components/DealerInfo/DealerInfo';
import CurrentTurnInfo from '../../components/CurrentTurnInfo/CurrentTurnInfo';
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
    const [currentMemberId, setCurrentMemberId] = useState<number | null>(null);
    const [currentUserId, setCurrentUserId] = useState<number | null>(null);
    const [myMemberId, setMyMemberId] = useState<number | null>(null);
    const [roomCode, setRoomCode] = useState<string | null>(null);
    const [showLeaveModal, setShowLeaveModal] = useState(false);
    const [showChat, setShowChat] = useState(false);
    const [showBetModal, setShowBetModal] = useState(false);
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

    const zoomRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
    const updateZoom = () => {
        const zoom = window.devicePixelRatio || 1;

        if (zoomRef.current) {
        zoomRef.current.style.transform = `scale(${1 / zoom})`;
        }
    };

    updateZoom();
    window.addEventListener('resize', updateZoom);

    return () => {
        window.removeEventListener('resize', updateZoom);
    };
    }, []);

    
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
        setShowBetModal(true);
      };

      const MIN_BET = 100;

      const handlePlaceBet = async (betAmount: number) => {
        if (!roomId || !user) {
          return;
        }

        const result = await server.makeBet(roomId, betAmount);
        
        if (result && result.success) {
          setShowBetModal(false);
        } else if (result && !result.success) {
          alert('Не удалось сделать ставку. Возможно, стол полон или произошла ошибка.');
        }
      };

      const handleCancelBet = () => {
        setShowBetModal(false);
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


    // Функция для отображения названий фаз игры
    const getPhaseDisplayName = (phase: string): string => {
        const phaseMap: Record<string, string> = {
            'waiting': 'ОЖИДАНИЕ',
            'waiting_for_bets': 'СТАВКИ',
            'playing': 'ИГРА',
            'player_turn': 'ХОДЫ ИГРОКОВ',
            'dealer_turn': 'ХОД ДИЛЛЕРА',
            'show_results': 'КОНЕЦ',
        };
        return phaseMap[phase] || phase;
    };

    // Определяем статус текущего игрока
    const myPlayer = useMemo(() => {
        if (!user || myMemberId === null) return null;
        return players.find(p => p.memberId === myMemberId);
    }, [players, myMemberId, user]);

    const isSpectator = myPlayer?.status === 'spectator';
    
    // Подсчитываем количество активных игроков (не спектаторов)
    const activePlayersCount = useMemo(() => {
        return players.filter(player => player.status !== 'spectator').length;
    }, [players]);

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
            
            if (roomInfo.currentMemberId !== undefined) setCurrentMemberId(roomInfo.currentMemberId);
            
            if (roomInfo.userId !== undefined) setCurrentUserId(roomInfo.userId);
            
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
        <div className="game-zoom-root" ref={zoomRef}>
            <SnowEffect />
        <div className="game-scale-wrapper">
           
        <div className="game-table-container">
            <img src={tableImgSrc} alt="Poker Table" className="game-table-image" />
        </div>
        
        <div id={GAME_FIELD} className={GAME_FIELD}>
            <GameTable
                players={players}
                playersScores={playersScores}
                myMemberId={myMemberId}
                currentMemberId={currentMemberId}
                currentUserId={currentUserId}
                getCardImage={getCardImage}
            />
            
            <DealerInfo
                dealerCards={dealerCards}
                dealerScore={dealerScore}
                getCardImage={getCardImage}
            />
            
            <CurrentTurnInfo
                players={players}
                currentMemberId={currentMemberId}
            />
            
            {/* лого снизу */}
            <div className="game-brand-logo" aria-label="CASINOCHKO">
                <span className="brand-white">CASIN</span>
                <span className="brand-yellow">OCHKO</span>
            </div>

            {/* карты и управление игрока */}
            {!isSpectator && (
                <PlayerGameControls
                    myCards={myCards}
                    myScore={myScore}
                    getCardImage={getCardImage}
                    onHit={handleHit}
                    onStand={handleStand}
                    onDouble={handleDouble}
                />
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

        <button className="game-button bet-button" onClick={handleBet}>
            Ставка
        </button>

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
            <RoomCodeMessage
                roomCode={roomCode}
                onClose={() => setRoomCode(null)}
            />
        )}

        <LeaveRoomModal
            isOpen={showLeaveModal}
            onLeave={handleLeaveRoom}
            onStay={handleStayInRoom}
        />
        <Chat 
            isOpen={showChat} 
            onClose={() => setShowChat(false)}
            roomId={roomId}
        />

        <BetModal
            isOpen={showBetModal}
            userBalance={user?.balance || 0}
            minBet={MIN_BET}
            isSpectator={isSpectator}
            activePlayersCount={activePlayersCount}
            onPlace={handlePlaceBet}
            onCancel={handleCancelBet}
        />

        {showResultModal && gameResult && (
            <GameResultModal
                status={gameResult.status}
                betAmount={gameResult.betAmount}
                onClose={handleCloseResultModal}
            />
        )}
        </div>
    </div>)
}

export default GamePage;