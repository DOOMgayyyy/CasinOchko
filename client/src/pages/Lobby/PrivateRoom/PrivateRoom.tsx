import React from 'react';
import './PrivateRoom.css';

export interface PrivateRoomProps {
    player: {
        name: string;
        balance: number;
    };
    onBack: () => void;
    onCreateRoom: () => void;
    onJoinRoom: () => void;
    onShowSideMenu: () => void;
    roomCreated?: {code: string, room_id: number} | null;
    onCloseRoomMessage?: () => void;
}

const PrivateRoom: React.FC<PrivateRoomProps> = ({ 
    player,
    onBack, 
    onCreateRoom, 
    onJoinRoom,
    onShowSideMenu,
    roomCreated,
    onCloseRoomMessage
}) => {
    return (
        <div className="private-room">
            <div className="private-room-background"></div>
            
            <header className="private-room-header">
                <div className="header-left">
                    <button className="menu-btn" onClick={onShowSideMenu}>☰</button>
                    <span className="player-name">{player.name}</span>
                </div>
                <div className="header-right">
                    <span className="balance-text">Ваш баланс: </span>
                    <span className="balance-amount">${player.balance}</span>
                    <button className="add-money-btn">+</button>
                </div>
            </header>

            <main className="private-room-main">
                <div className="private-room-logo">
                    <span className="logo-casino">CASIN</span>
                    <span className="logo-ochko">OCHKO</span>
                </div>
                
                <div className="private-room-buttons">
                    <div className="create-room-section">
                        <button className="private-room-btn create-btn" onClick={onCreateRoom}>
                            Создать 
                        </button>
                        
                        {roomCreated && (
                            <div className="room-created-message">
                                <div className="room-created-header">
                                    <span className="success-icon">✅</span>
                                    <span className="success-text">Комната создана!</span>
                                    {onCloseRoomMessage && (
                                        <button className="close-message-btn" onClick={onCloseRoomMessage}>×</button>
                                    )}
                                </div>
                                <div className="room-code-display">
                                    <div className="room-code-label">Код комнаты:</div>
                                    <div className="room-code-value">{roomCreated.code}</div>
                                </div>
                                <div className="room-id">ID: {roomCreated.room_id}</div>
                                <div className="room-instruction">Поделитесь кодом с друзьями!</div>
                            </div>
                        )}
                    </div>
                    
                    <button className="private-room-btn join-btn" onClick={onJoinRoom}>
                        Присоединиться
                    </button>
                    <button className="back-btn" onClick={onBack}>
                        &lt;назад
                    </button>
                </div>

                
            </main>
        </div>
    );
};

export default PrivateRoom;