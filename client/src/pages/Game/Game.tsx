import React, { useContext, useEffect, useState, useMemo, useRef } from 'react';
import CONFIG from '../../config';
import Button from '../../components/Button/Button';
import { IBasePage, PAGES } from '../PageManager';
import Game from '../../game/Game';
import { Canvas, useCanvas } from '../../services/canvas';

import cardJH from '../../assets/img/cards/JH.png';
import cardKH from '../../assets/img/cards/KH.png';

import tableImgSrc from '../../assets/img/Table/Table.png';
import chatIcon from '../../assets/img/chat_bubble.svg';
import './Game.scss';

const GAME_FIELD = 'game-field';

const GamePage: React.FC<IBasePage> = (props: IBasePage) => {
    const { WINDOW, SPRITE_SIZE } = CONFIG;
    const { setPage } = props;
    
    let game: Game | null = null;
    // инициализация канваса
    let canvas: Canvas | null = null;
    const Canvas = useCanvas(render);
    let interval: NodeJS.Timeout | null = null;

    const currentPlayerId = 1;
    // инициализация стола
    const [tableImage, setTableImage] = useState<HTMLImageElement | null>(null);

    // информация об игроках
    const [players, setPlayers] = useState([
        { id: 1, name: 'Decibek' + ':', balance: 42, score: 20, position: 'left-top' },
        { id: 2, name: 'DOOMgay' + ':', balance: 66, score: 19, position: 'left-middle' },
        { id: 3, name: 'safevitya' + ':', balance: 52, score: 17, position: 'left-bottom' },
        { id: 4, name: 'Player228' + ':', balance: 2000, score: 17, position: 'right-bottom' },
        { id: 5, name: 'Lotov123' + ':', balance: 220, score: 18, position: 'right-middle' },
        { id: 6, name: 'Alexey Trusov' + ':', balance: 15, score: 24, position: 'right-top' },
      ]);

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
        setPage(PAGES.LOBBY);
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

    return (<div className='game-page'>
        <div className="game-scale-wrapper">
           
        {/* надписи с инфой игроков за столом */}
        <div id={GAME_FIELD} className={GAME_FIELD}>
            <div className="players">
                {players.map(player => (
                    <div className={`player-slot ${player.position} ${player.id === currentPlayerId ? 'active-player' : ''}`} key={player.id}>
                    <span className="name">{player.name}</span>
                    <span className="balance">${player.balance}</span>
                    <span className="score">{player.score}</span>
                    </div>
                ))}
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
                <div className="card">
                    <img src={cardJH}/>
                </div>
                <div className="card">
                    <img src={cardKH}/>
                </div>
            </div>

        <div className='timer-div'>
            <span className='timer-span'>Таймер хода:</span>
            <span className='timer-count'>⏱ 15</span>
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
            <span className='count-number'>17</span>
        </div>

        <button className="back-to-lobby-button" onClick={handleBackToLobby} />
            <div className="top-right-controls">
            <span className="room-id">Комната AD12F </span>
                <button className="chat-button" onClick={handleChatToggle}>
                    <img src={chatIcon} alt="Chat" className="chat-icon" />
                </button>
            </div>
        </div>
        </div>
    </div>)
}

export default GamePage;