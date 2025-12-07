import React, { useState, useContext } from 'react';
import './Lobby.scss';
import SideMenu from './SideMenu/SideMenu';
import { IBasePage, PAGES } from '../PageManager';
import { StoreContext, ServerContext } from '../../App';

import AdReward from './AdReward/AdReward'; 
import MenuIcon from '../../assets/img/toppanel/sidebarmenu.png';
import SnowEffect from '../../components/SnowEffect/SnowEffect';
import garlandImg from '../../assets/img/HNY/garland.svg';
import snowman1 from '../../assets/img/HNY/snowman1.svg';
import snowman2 from '../../assets/img/HNY/snowman2.svg';

import video from '../../assets/ads/ad.mp4';

export interface LobbyProps extends IBasePage {}

const Lobby: React.FC<LobbyProps> = ({ setPage }) => {
    // 2. Получаем store и server из контекста
    const store = useContext(StoreContext);
    const server = useContext(ServerContext);
    
    // 3. Получаем актуальные данные пользователя из store
   // const [player, setPlayer] = useState(() => {
       //  const user = store.getUser();
        //  return user ?{
           //  name: user.name,
           //  balance: user.balance
        //} : null;
    // });
    //==================DEV-Заглушка=====================
    const [player, setPlayer] = useState(() => {
        const user = store.getUser();
        return user ? {
            name: user.name,
            balance: user.balance
        } : null;
    });

    //==================DEV-Заглушка=====================
    // const [player, setPlayer] = useState(() => {
    //     return {
    //         name: 'dev',
    //         balance: 123
    //     };
    //     console.log('No user data in store');
    // });
    //===================================================

    const [showSideMenu, setShowSideMenu] = useState(false);
    const [showAdModal, setShowAdModal] = useState(false);

    const handleQuickStart = async () => {
        const result = await server.quickStart();
        if (result) {
            store.setCurrentRoomId(result.id);
            setPage(PAGES.GAME);
        }

    };
    
    // 4. Функция выхода теперь вызывает метод сервера и очищает данные
    const handleLogout = async () => {
        await server.logout(); // Вызываем метод logout из Server.ts
        setPage(PAGES.LOGIN); // Перенаправляем на страницу входа
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

    // 5. Защита: если данных пользователя нет, перенаправляем на логин
    if (!player) {
        // Это предотвратит ошибку, если пользователь не авторизован
        setPage(PAGES.LOGIN);
        return null; 
    }

    // Общий компонент SideMenu для переиспользования
    const sideMenuComponent = showSideMenu && (
        <SideMenu
            player={player}
            onClose={() => setShowSideMenu(false)}
            onEditName={() => {
                // Получить обновленные данные пользователя из Store
                const updatedUser = store.getUser();
                if (updatedUser) {
                    // Обновить локальное состояние player
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

    // Общий компонент AdReward для переиспользования
    const adModalComponent = showAdModal && (
        <AdReward
            videoUrl={video}
            onClose={() => setShowAdModal(false)}
            onSuccess={handleAdSuccess}
        />
    );

    return (
        <div className="lobby">
            <div className="lobby-background"></div>
            <SnowEffect />
            <div className="lobby-garland">
                <img src={garlandImg} alt="Гирлянда" className="garland-image" />
            </div>

            <header className="lobby-header">
                <div className="header-left">
                    <button className="menu-btn" onClick={() => setShowSideMenu(true)}><img src={MenuIcon}  /></button>
                    {/* 6. Данные берутся из 'player', полученного из store */}
                    <span className="player-name">{player.name}</span>
                </div>

                <div className="header-right">
                    <span className="balance-text">Ваш баланс: </span>
                    <span className="balance-amount">${player.balance}</span>
                    
                    <button 
                        className="add-money-btn" 
                        onClick={() => setShowAdModal(true)}
                    >
                    </button>
                </div>
            </header>

            <main className="lobby-main">
                <div className="logo">
                    <span className="logo-casino">CASIN</span>
                    <span className="logo-ochko">OCHKO</span>
                </div>

                <button className="lobby-btn quick-game-btn" onClick={handleQuickStart}>
                    Быстрая игра
                </button>

                <button className="lobby-btn private-room-btn" onClick={() => setPage(PAGES.PRIVATE_ROOM)}>
                    Приватная комната
                </button>

                <button className="lobby-btn leaderboard-btn" onClick={() => setPage(PAGES.LEADERBOARD)}>
                    Таблица лидеров
                </button>
            </main>

            <div className="lobby-snowmen-container">
                <img src={snowman1} alt="Snowman" className="lobby-snowman lobby-snowman-left" />
                <img src={snowman2} alt="Snowman" className="lobby-snowman lobby-snowman-right" />
            </div>

            {sideMenuComponent}

            {adModalComponent}
        </div>
    );
};

export default Lobby;