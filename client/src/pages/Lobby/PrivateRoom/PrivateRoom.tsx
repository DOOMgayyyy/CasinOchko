import React, { useState, useContext } from 'react';
import './PrivateRoom.scss';
import MenuIcon from '../../../assets/img/toppanel/sidebarmenu.png';
import PlusIcon from '../../../assets/img/toppanel/topupthebalance.png';
import { ServerContext, StoreContext } from '../../../App';
import { PAGES } from '../../PageManager';

export interface PrivateRoomProps {
    player: {
        name: string;
        balance: number;
    };
    onBack: () => void;
    onCreateRoom: () => void;
    onShowSideMenu: () => void;
    onShowAdModal: () => void;
    setPage: (page: PAGES) => void; 
}

const PrivateRoom: React.FC<PrivateRoomProps> = ({ 
    player,              // Данные игрока (имя и баланс) для отображения в заголовке
    onBack,              // Callback для возврата к основному лобби
    onCreateRoom,        // Callback для создания новой приватной комнаты
    //onJoinRoom,          // Callback для присоединения к существующей комнате по коду
    onShowSideMenu,      // Callback для открытия бокового меню с настройками
    onShowAdModal,       // Callback для открытия модального окна с рекламой (пополнение баланса)
    setPage
}) => {
    const [joinCode, setJoinCode] = useState('');
    const [showJoinInput, setShowJoinInput] = useState(false);

    const server = useContext(ServerContext);
    const store = useContext(StoreContext);

    const handleJoinRoom = async (code: string) => {
        try {
            const roomData = await server.joinPrivateRoom(code);
            console.log('Room data:', roomData);

            if (roomData && roomData.id) {
                console.log('Successfully joined room:', roomData);
                store.setCurrentRoomId(roomData.id);
                setPage(PAGES.GAME);
            } else {
                console.log('Failed to join room.');
            }
        } catch (error) {
            console.error('Exception during joinPrivateRoom:', error);
        }
    };
    
    const handleJoinClick = () => {
        if (showJoinInput && joinCode.trim()) {
            handleJoinRoom(joinCode.toUpperCase());
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