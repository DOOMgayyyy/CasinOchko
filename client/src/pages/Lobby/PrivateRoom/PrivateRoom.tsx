import React, { useState, useContext } from 'react';
import './PrivateRoom.scss';
import MenuIcon from '../../../assets/img/toppanel/sidebarmenu.png';
import PlusIcon from '../../../assets/img/toppanel/topupthebalance.png';
import { ServerContext, StoreContext } from '../../../App';
import { IBasePage, PAGES } from '../../PageManager';
import SideMenu from '../SideMenu/SideMenu';
import AdReward from '../AdReward/AdReward';

const PrivateRoom: React.FC<IBasePage> = ({ setPage }) => {
    const server = useContext(ServerContext);
    const store = useContext(StoreContext);
    
    const user = store.getUser();
    const [player, setPlayer] = useState(() => {
        return user ? {
            name: user.name,
            balance: user.balance
        } : null;
    });

    const [showSideMenu, setShowSideMenu] = useState(false);
    const [showAdModal, setShowAdModal] = useState(false);
    const [joinCode, setJoinCode] = useState('');
    const [showJoinInput, setShowJoinInput] = useState(false);

    const handleCreateRoom = async () => {
        const result = await server.createPrivateRoom();
        
        if (result && result.private_code) {
            sessionStorage.setItem('roomCode', result.private_code);
            store.setCurrentRoomId(result.id);
            setPage(PAGES.GAME);
        }
    };

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

    const handleBackToLobby = () => {
        setPage(PAGES.LOBBY);
    };

    const handleLogout = async () => {
        await server.logout();
        setPage(PAGES.LOGIN);
    };

    const handleAdSuccess = (newBalance: number) => {
        setPlayer((prev) =>
            prev ? { ...prev, balance: newBalance } : prev
        );

        if (typeof store.setUser === 'function') {
            const currentUser = store.getUser();
            if (currentUser) {
                store.setUser({ ...currentUser, balance: newBalance });
            }
        }
    };

    // Защита: если данных пользователя нет, перенаправляем на логин
    if (!player) {
        setPage(PAGES.LOGIN);
        return null;
    }

    const sideMenuComponent = showSideMenu && (
        <SideMenu
            player={player}
            onClose={() => setShowSideMenu(false)}
            onEditName={() => {
                const updatedUser = store.getUser();
                if (updatedUser) {
                    setPlayer({
                        name: updatedUser.name,
                        balance: updatedUser.balance
                    });
                }
            }}
            onShowRules={() => setPage(PAGES.RULES)}
            onShowAuthors={() => setPage(PAGES.AUTHORS)}
            onLogout={handleLogout}
        />
    );

    const adModalComponent = showAdModal && (
        <AdReward
            videoUrl={require('../../../assets/ads/ad.mp4')}
            onClose={() => setShowAdModal(false)}
            onSuccess={handleAdSuccess}
        />
    );

    return (
        <>
            <div className="private-room">
                <div className="private-room-background"></div>
                
                <header className="private-room-header">
                    <div className="header-left">
                        <button className="menu-btn" onClick={() => setShowSideMenu(true)}><img src={MenuIcon}/></button>
                        <span className="player-name">{player.name}</span>
                    </div>
                    <div className="header-right">
                        <span className="balance-text">Ваш баланс: </span>
                        <span className="balance-amount">${player.balance}</span>
                        <button className="add-money-btn" onClick={() => setShowAdModal(true)}><img src={PlusIcon}  /></button>
                    </div>
                </header>

                <main className="private-room-main">
                    <div className="private-room-logo">
                        <span className="logo-casino">CASIN</span>
                        <span className="logo-ochko">OCHKO</span>
                    </div>
                    
                    <div className="private-room-buttons">
                        <div className="create-room-section">
                            <button className="private-room-btn create-btn" onClick={handleCreateRoom}>
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
                        
                        <button className="back-btn" onClick={handleBackToLobby}>
                            &lt;назад
                        </button>
                    </div>
                </main>
            </div>
            {sideMenuComponent}
            {adModalComponent}
        </>
    );
};

export default PrivateRoom;