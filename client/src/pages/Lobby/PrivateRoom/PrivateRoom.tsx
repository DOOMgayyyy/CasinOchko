import React, { useState } from 'react';
import './PrivateRoom.scss';
import MenuIcon from '../../../assets/img/toppanel/sidebarmenu.png';
import PlusIcon from '../../../assets/img/toppanel/topupthebalance.png';

export interface PrivateRoomProps {
    player: {
        name: string;
        balance: number;
    };
    onBack: () => void;
    onCreateRoom: () => void;
    onJoinRoom: (code: string) => void;
    onShowSideMenu: () => void;
    onShowAdModal: () => void;
    roomCreated?: {code: string, room_id: number} | null;
    onCloseRoomMessage?: () => void;
}

const PrivateRoom: React.FC<PrivateRoomProps> = ({ 
    player,
    onBack, 
    onCreateRoom, 
    onJoinRoom,
    onShowSideMenu,
    onShowAdModal,
    roomCreated,
    onCloseRoomMessage
}) => {
    const [joinCode, setJoinCode] = useState('');
    const [showJoinInput, setShowJoinInput] = useState(false);
    const [copySuccess, setCopySuccess] = useState(false);

    const handleJoinClick = () => {
        if (showJoinInput && joinCode.trim()) {
            onJoinRoom(joinCode.toUpperCase());
            setJoinCode('');
            setShowJoinInput(false);
        } else {
            setShowJoinInput(true);
        }
    };

    const handleCancelJoin = () => {
        setShowJoinInput(false);
        setJoinCode('');
    };

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value.toUpperCase().replace(/[^A-Z]/g, '');
        if (value.length <= 4) {
            setJoinCode(value);
        }
    };
    // Ручка для копирования кода комнаты (при клике на код комнаты))
    const handleCopyCode = async () => {
        if (roomCreated?.code) {
            await navigator.clipboard.writeText(roomCreated.code);
            setCopySuccess(true);
            setTimeout(() => setCopySuccess(false), 2000);
        }
    };

    return (
        <div className="private-room">
            <div className="private-room-background"></div>
            
            <header className="private-room-header">
                <div className="header-left">
                    <button className="menu-btn" onClick={onShowSideMenu}><img src={MenuIcon}/></button>
                    <span className="player-name">{player.name}</span>
                </div>
                <div className="header-right">
                    <span className="balance-text">Ваш баланс: </span>
                    <span className="balance-amount">${player.balance}</span>
                    <button className="add-money-btn" onClick={onShowAdModal}><img src={PlusIcon}  /></button>
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
                                    <div 
                                        className="room-code-value clickable" 
                                        onClick={handleCopyCode}
                                        title="Нажмите, чтобы скопировать код">
                                        {roomCreated.code}
                                    </div>
                                </div>
                                <div className="room-id">ID: {roomCreated.room_id}</div>
                                {copySuccess && (
                                    <div className="copy-success">Код скопирован!</div>
                                )}
                            </div>
                        )}
                    </div>
                    
                    <div className="join-room-section">
                        {showJoinInput ? (
                            <div className="join-input-container">
                                <div className="create-code-text">Введите код</div>
                                
                                {/* Прямоугольник-фон */}
                                <div className="join-input-background"></div>
                                
                                {/* Поле ввода */}
                                <input
                                    type="text"
                                    value={joinCode}
                                    onChange={handleInputChange}
                                    
                                    maxLength={4}
                                    autoFocus
                                />
                                
                                {/* Кнопки */}
                                <div className="join-buttons-container">
                                    <button 
                                        className="back-join-btn" 
                                        onClick={handleCancelJoin}
                                    >
                                        &lt; назад
                                    </button>
                                    <button 
                                        className="connect-join-btn" 
                                        onClick={handleJoinClick}
                                    >
                                        &gt; подключиться
                                    </button>
                                </div>
                            </div>
                        ) : (
                            <button className="private-room-btn join-btn" onClick={handleJoinClick}>
                                Присоединиться
                            </button>
                        )}
                    </div>
                    
                    <button className="back-btn" onClick={onBack}>
                        &lt;назад
                    </button>
                </div>
            </main>
        </div>
    );
};

export default PrivateRoom;