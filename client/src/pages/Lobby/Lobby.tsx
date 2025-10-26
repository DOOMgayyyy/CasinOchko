import React, { useState, useContext } from 'react';
import './Lobby.css';
import SideMenu from './SideMenu/SideMenu';
import PrivateRoom from './PrivateRoom/PrivateRoom';
import { IBasePage, PAGES } from '../PageManager';
import { StoreContext, ServerContext } from '../../App'; // 1. Импортируем контексты

export interface LobbyProps extends IBasePage {}

const Lobby: React.FC<LobbyProps> = ({ setPage }) => {
    // 2. Получаем store и server из контекста
    const store = useContext(StoreContext);
    const server = useContext(ServerContext);
    
    // 3. Получаем актуальные данные пользователя из store
    //const player = store.getUser();
    //=================DEV ЗАГЛУШКА=============================
    const [player] = useState({    
    name: 'dev',
    balance: 99999,
    });
    //==========================================================

    const [showSideMenu, setShowSideMenu] = useState(false);
    const [showPrivateRoomPage, setShowPrivateRoomPage] = useState(false);
    ///
    const [playerStats] = useState({
        totalGames: 156,
        totalWins: 89,
        totalMoney: 25400,
        totalHours: 47
    });

    const handleCreateRoom = () => {
        console.log('Create private room');
    };

    const handleJoinRoom = () => {
        console.log('Join private room');
    };
    
    // 4. Функция выхода теперь вызывает метод сервера и очищает данные
    const handleLogout = async () => {
        await server.logout(); // Вызываем метод logout из Server.ts
        setPage(PAGES.LOGIN); // Перенаправляем на страницу входа
    };

    // 5. Защита: если данных пользователя нет, перенаправляем на логин
    if (!player) {
        // Это предотвратит ошибку, если пользователь не авторизован
        setPage(PAGES.LOGIN);
        return null; 
    }

    // Если показываем страницу приватной комнаты
    if (showPrivateRoomPage) {
        return (
            <PrivateRoom
                player={player}
                onBack={() => setShowPrivateRoomPage(false)}
                onCreateRoom={handleCreateRoom}
                onJoinRoom={handleJoinRoom}
                onShowSideMenu={() => setShowSideMenu(true)}
            />
        );
    }

    return (
        <div className="lobby">
            <div className="lobby-background"></div>

            <header className="lobby-header">
                <div className="header-left">
                    <button className="menu-btn" onClick={() => setShowSideMenu(true)}>☰</button>
                    {/* 6. Данные берутся из 'player', полученного из store */}
                    <span className="player-name">{player.name}</span>
                </div>

                <div className="header-right">
                    <span className="balance-text">Ваш баланс: </span>
                    <span className="balance-amount">${player.balance}</span>
                    <button className="add-money-btn">+</button>
                </div>
            </header>

            <main className="lobby-main">
                <div className="logo">
                    <span className="logo-casino">CASIN</span>
                    <span className="logo-ochko">OCHKO</span>
                </div>

                <button className="lobby-btn quick-game-btn" onClick={() => setPage(PAGES.QUICK_GAME)}>
                    Быстрая игра
                </button>

                <button className="lobby-btn blackjack-btn" onClick={() => setPage(PAGES.GAME)}>
                    Blackjack
                </button>

                <button className="lobby-btn private-room-btn" onClick={() => setShowPrivateRoomPage(true)}>
                    Приватная комната
                </button>

                <button className="lobby-btn leaderboard-btn" onClick={() => setPage(PAGES.LEADERBOARD)}>
                    Таблица лидеров
                </button>
            </main>

            {showSideMenu && (
                <SideMenu
                    player={player}
                    stats={playerStats}
                    onClose={() => setShowSideMenu(false)}
                    onEditName={() => console.log('Edit name clicked')}
                    onShowRules={() => setPage(PAGES.RULES)}
                    onShowAuthors={() => setPage(PAGES.AUTHORS)}
                    onLogout={handleLogout}
                />
            )}
        </div>
    );
};

export default Lobby;