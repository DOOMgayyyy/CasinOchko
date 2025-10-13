import React, { useContext, useEffect, useState, useMemo, useRef } from 'react';
import CONFIG from '../../config';
import Button from '../../components/Button/Button';
import { IBasePage, PAGES } from '../PageManager';
import Game from '../../game/Game';
import { Canvas, useCanvas } from '../../services/canvas';

import tableImgSrc from '../../assets/img/Table/Table.png';
import chatIcon from '../../assets/img/chat_bubble.svg';

import './Game.scss';

const GAME_FIELD = 'game-field';
const GREEN = '#00e81c';

const GamePage: React.FC<IBasePage> = (props: IBasePage) => {
    const { WINDOW, SPRITE_SIZE } = CONFIG;
    const { setPage } = props;
    // для положения кнопок
    const [isVerticalLayout, setIsVerticalLayout] = useState(false);
    let game: Game | null = null;
    // инициализация канваса
    let canvas: Canvas | null = null;
    const Canvas = useCanvas(render);
    let interval: NodeJS.Timeout | null = null;
    // инициализация стола
    const [tableImage, setTableImage] = useState<HTMLImageElement | null>(null);

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
                  offsetY: -80,   // смещение вверх вниз
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
    
      const handleDouble = () => {
        console.log('Double button clicked');
        // Логика для удвоения ставки
      };
    
      const handleBackToLobby = () => {
        setPage(PAGES.LOBBY);
      };
    
      const toggleLayout = () => {
        setIsVerticalLayout(!isVerticalLayout);
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
        <div id={GAME_FIELD} className={GAME_FIELD}><div className={`game-controls ${isVerticalLayout ? 'vertical' : 'horizontal'}`}>
            <button className="game-button hit-button" onClick={handleHit}>
            Взять ещё
            </button>
            <button className="game-button stand-button" onClick={handleStand}>
            Стоп
            </button>
            <button className="game-button split-button" onClick={handleSplit}>
            Сплит
            </button>
            <button className="game-button double-button" onClick={handleDouble}>
            Удвоить
            </button>
        </div>

        <button className="back-to-lobby-button" onClick={handleBackToLobby}>
            ← Назад в лобби
        </button>

        <div className="top-right-controls">
        <span className="room-id">Комната AD12F </span>
            <button className="chat-button" onClick={handleChatToggle}>
            <img src={chatIcon} alt="Chat" className="chat-icon" />
            </button>
            <button className="layout-toggle-button" onClick={toggleLayout}>
            {isVerticalLayout ? '⬇' : '⬅'}
            </button>
        </div></div>
    </div>)
}

export default GamePage;