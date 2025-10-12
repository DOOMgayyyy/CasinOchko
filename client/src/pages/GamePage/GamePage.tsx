import React, { useRef, useEffect, useState } from 'react';
import './GamePage.scss';
import { IBasePage, PAGES } from '../PageManager';
import tableImage from '../../assets/img/Стол (1).svg';
import chatIcon from '../../assets/img/chat_bubble.svg';

interface GameCanvasProps {
  width: number;
  height: number;
}

const GameCanvas: React.FC<GameCanvasProps> = ({ width, height }) => {
  const canvasRef = useRef<HTMLCanvasElement>(null);

  useEffect(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    // Очистка канваса
    ctx.fillStyle = '#181818';
    ctx.fillRect(0, 0, width, height);

    // Загрузка и отрисовка изображения стола
    const table = new Image();
    table.onload = () => {
      // Вычисляем размеры для сохранения пропорций
      const tableAspectRatio = table.width / table.height;
      const canvasAspectRatio = width / height;
      
      let drawWidth, drawHeight, drawX, drawY;
      
      drawWidth = width * 0.7;
      drawHeight = drawWidth / tableAspectRatio;
      
      // Центрируем по горизонтали, привязываем к верхнему краю с небольшим отступом
      drawX = (width - drawWidth) / 2;
      drawY = 50; // Отступ от верхнего края
      
      ctx.drawImage(table, drawX, drawY, drawWidth, drawHeight);
    };
    table.src = tableImage;

  }, [width, height]);

  return (
    <canvas
      ref={canvasRef}
      width={width}
      height={height}
      className="game-canvas"
    />
  );
};

const GamePage: React.FC<IBasePage> = ({ setPage }) => {
  const [canvasSize, setCanvasSize] = useState({ width: 0, height: 0 });
  const [isVerticalLayout, setIsVerticalLayout] = useState(false);

  useEffect(() => {
    const updateCanvasSize = () => {
      setCanvasSize({
        width: window.innerWidth,
        height: window.innerHeight
      });
    };

    updateCanvasSize();
    window.addEventListener('resize', updateCanvasSize);
    
    return () => window.removeEventListener('resize', updateCanvasSize);
  }, []);

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

  return (
    <div className="game-page">
      <GameCanvas width={canvasSize.width} height={canvasSize.height} />
      
      <div className={`game-controls ${isVerticalLayout ? 'vertical' : 'horizontal'}`}>
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
      </div>
    </div>
  );
};

export default GamePage;
