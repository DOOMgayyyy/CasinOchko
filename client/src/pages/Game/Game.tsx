import React, { useContext, useEffect, useState, useMemo, useRef } from 'react';
import CONFIG from '../../config';
import Button from '../../components/Button/Button';
import { IBasePage, PAGES } from '../PageManager';
import Game from '../../game/Game';
import { Canvas, useCanvas } from '../../services/canvas';
import { ServerContext, StoreContext } from '../../App';
import { TPlayer, TRoomInfoResponse, TTimer } from '../../services/server/types';

import cardJH from '../../assets/img/cards/JH.png';
import cardKH from '../../assets/img/cards/KH.png';

import tableImgSrc from '../../assets/img/Table/Table.png';
import chatIcon from '../../assets/img/chat_bubble.svg';
import './Game.scss';

const GAME_FIELD = 'game-field';

const GamePage: React.FC<IBasePage> = (props: IBasePage) => {
    const { WINDOW, SPRITE_SIZE } = CONFIG;
    const { setPage } = props;
    const server = useContext(ServerContext);
    const store = useContext(StoreContext);
    
    let game: Game | null = null;
    // инициализация канваса
    let canvas: Canvas | null = null;
    const Canvas = useCanvas(render);
    let interval: NodeJS.Timeout | null = null;

    // инициализация стола
    const [tableImage, setTableImage] = useState<HTMLImageElement | null>(null);

    // Состояние игры из сервера
    const [myCards, setMyCards] = useState<string[]>([]);
    const [players, setPlayers] = useState<TPlayer[]>([]);
    const [timer, setTimer] = useState<TTimer | null>(null);
    const [myMemberId, setMyMemberId] = useState<number | null>(null);
    const [roomCode, setRoomCode] = useState<string | null>(null);
    const [copySuccess, setCopySuccess] = useState(false);
    const [showLeaveModal, setShowLeaveModal] = useState(false);
    
    // Получаем roomId из store
    const roomId = store.getCurrentRoomId();
    const user = store.getUser();

    // функция отрисовки одного кадра сцены
    function render(FPS: number): void {
        if (canvas && game) {
            canvas.clear();

            /**********************/
            /* фон покерного стола */
            /**********************/
            if (tableImage) {
                canvas.drawImageFit(tableImage, {
                  mode: 'contain',
                  alignX: 'center',
                  alignY: 'center',
                  zoom: 0.8,     // отдаление
                  offsetX: 0,    // смещение в стороны
                  offsetY: -78,   // смещение вверх вниз
                });
            }

            /************************/
            /* отрендерить картинку */
            /************************/
            canvas.render();
        }
    }

    /****************/
    /* Mouse Events */
    /****************/
    const mouseMove = (_x: number, _y: number) => {
    }

    const mouseClick = (_x: number, _y: number) => {
    }

    const mouseRightClick = () => {
    }
    /****************/

    const handleHit = () => {
        console.log('Hit button clicked');
        // Логика для взятия карты
      };
    
      const handleStand = () => {
        console.log('Stand button clicked');
        // Логика для завершения хода
      };
    
      const handleSplit = () => {
        console.log('Split button clicked');
        // Логика для сплита
      };
    
      const handleBet = () => {
        console.log('Bet button clicked');
        // Логика для удвоения ставки
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
          setPage(PAGES.LOBBY);
        }
        setShowLeaveModal(false);
      };

      const handleStayInRoom = () => {
        setShowLeaveModal(false);
      };
        
      const handleChatToggle = () => {
        console.log('Chat toggle clicked');
        // Логика для открытия/закрытия чата
      };

    useEffect(() => {
        // инициализация игры
        game = new Game();
        canvas = Canvas({
            parentId: GAME_FIELD,
            WIDTH: WINDOW.WIDTH * SPRITE_SIZE,
            HEIGHT: WINDOW.HEIGHT * SPRITE_SIZE,
            WINDOW,
            callbacks: {
                mouseMove,
                mouseClick,
                mouseRightClick,
            },
        });
        return () => {
            // деинициализировать все экземпляры
            game?.destructor();
            canvas?.destructor();
            canvas = null;
            game = null;
            if (interval) {
                clearInterval(interval);
                interval = null;
            }
        }
    });

    useEffect(() => {
        const img = new Image();
        img.src = tableImgSrc;
        img.onload = () => setTableImage(img);
    }, []);

    useEffect(() => {
        const code = sessionStorage.getItem('roomCode');
        if (code) {
            setRoomCode(code);
            sessionStorage.removeItem('roomCode');
        }
    }, []);

    const handleCopyCode = async () => {
        if (roomCode) {
            await navigator.clipboard.writeText(roomCode);
            setCopySuccess(true);
            setTimeout(() => setCopySuccess(false), 2000);
        }
    };

    // Game loop - каждую секунду запрашиваем обновления состояния игры
    useEffect(() => {
        if (!roomId) {
            // Если нет roomId, возвращаемся в лобби
            setPage(PAGES.LOBBY);
            return;
        }

        // Функция обработки обновлений игры
        const handleGameUpdate = (roomInfo: TRoomInfoResponse) => {
            setMyCards(roomInfo.myCards);
            setPlayers(roomInfo.players);
            setTimer(roomInfo.timer);
            
            // Находим себя в списке игроков
            if (user) {
                const myPlayer = roomInfo.players.find(p => p.userId === user.id);
                if (myPlayer) {
                    setMyMemberId(myPlayer.memberId);
                }
            }
        };

        // Запускаем game loop
        server.startGameLoop(roomId, handleGameUpdate);

        // Очистка при размонтировании компонента
        return () => {
            server.stopGameLoop();
        };
    }, [roomId, server, user, setPage]);

    return (<div className='game-page'>
        <div className="game-scale-wrapper">
           
        {/* надписи с инфой игроков за столом */}
        <div id={GAME_FIELD} className={GAME_FIELD}>
            <div className="players">
                {players.map((player, index) => {
                    const positions = ['left-top', 'left-middle', 'left-bottom', 'right-bottom', 'right-middle', 'right-top'];
                    const position = positions[index] || 'left-top';
                    const isCurrentPlayer = myMemberId !== null && player.memberId === myMemberId;
                    const isActiveTurn = timer?.currentPlayerId === player.memberId;
                    const score = player.cards.length * 5; // Реализовать правильный расчет очков
                    
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
             {/* дилер */}
             <div className='diller-slot'>
                <span className="diller-name">Дилер: </span>
                <span className="diller-score">21</span>
            </div>
            {/* лого снизу */}
            <div className="game-brand-logo" aria-label="CASINOCHKO">
                <span className="brand-white">CASIN</span>
                <span className="brand-yellow">OCHKO</span>
            </div>

            {/* карты справа */}
            <div className="player-cards">
                <span className="your-cards-label">Ваши карты:</span>
            </div>
            <div className="my-cards">
                {myCards.length > 0 ? (
                    myCards.map((card, index) => (
                        <div className="card" key={index}>
                            <span>{card}</span>
                        </div>
                    ))
                ) : (
                    <div className="card">
                        <span>Нет карт</span>
                    </div>
                )}
            </div>

        <div className='timer-div'>
            <span className='timer-span'>Таймер хода:</span>
            <span className='timer-count'>
                ⏱ {timer && timer.timeLeft !== null ? timer.timeLeft : '—'}
            </span>
        </div>

        <div className='game-controls vertical'>

            <button className="game-button split-button" onClick={handleSplit}>
                Сплит
            </button>
            <button className="game-button hit-button" onClick={handleHit}>
                Взять карту
            </button>
            <button className="game-button stand-button" onClick={handleStand}>
                Отказаться
            </button>
        </div>
        <button className="game-button bet-button" onClick={handleBet}>
            Ставка
        </button>

        <div className='count-div'>
            <span className='count-span'>Ваши очки:</span>
            <span className='count-number'>{myCards.length * 5}</span>
        </div>

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
    </div>)
}

export default GamePage;