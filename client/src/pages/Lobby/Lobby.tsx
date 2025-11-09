import React, { useState, useContext, useEffect } from 'react';
import './Lobby.css';
import SideMenu from './SideMenu/SideMenu';
import PrivateRoom from './PrivateRoom/PrivateRoom';
import { IBasePage, PAGES } from '../PageManager';
import { StoreContext, ServerContext } from '../../App';
import { TUserStats } from '../../services/server/types'; 


export interface LobbyProps extends IBasePage {}

const Lobby: React.FC<LobbyProps> = ({ setPage }) => {
    // 2. Получаем store и server из контекста
    const store = useContext(StoreContext);
    const server = useContext(ServerContext);
    
    // 3. Получаем актуальные данные пользователя из store
    const [player, setPlayer] = useState(() => {
        const user = store.getUser();
        return user ?{
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
    const [showPrivateRoomPage, setShowPrivateRoomPage] = useState(false);
    const [roomCreated, setRoomCreated] = useState<{code: string, room_id: number} | null>(null);
    
    const [playerStats, setPlayerStats] = useState<TUserStats>({
        totalGames: 0,
        totalWins: 0,
        totalMoney: 0,
        totalHours: 0
    });


    useEffect(() => {
        let cancelled = false;

        const loadStats = async () => {
            try {
                const stats = await server.getUserStat();
                if (!cancelled && stats) {
                    console.log(stats);
                    setPlayerStats(stats);
                }
            } catch (e) {
                // ошибки обрабатываются внутри Server
                console.error(e);
            }
        };

            // вызываем только если пользователь авторизован
        if (player) {
            loadStats();
        }

        return () => {
            cancelled = true;
        };
    }, [server, player]);

    

    const handleCreateRoom = async () => {
        try {
            const result = await server.createPrivateRoom();
            
            if (result && result.private) {
                const { code, room_id } = result.private;
                
                // Сохраняем данные комнаты для отображения на странице
                setRoomCreated({ code, room_id });
                     
            } else {
                server.showErrorCb({
                    code: 9001,
                    text: 'Не удалось создать комнату. Попробуйте еще раз.'
                });
            }
        } catch (error) {
            server.showErrorCb({
                code: 9002,
                text: 'Произошла ошибка при создании комнаты.'
            });
        }
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
                roomCreated={roomCreated}
                onCloseRoomMessage={() => setRoomCreated(null)}
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
            )}
        </div>
    );
};

export default Lobby;