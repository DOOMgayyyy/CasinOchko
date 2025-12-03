import React, { useContext, useEffect, useState } from 'react';
import { IBasePage, PAGES } from '../PageManager';
import { StoreContext, ServerContext } from '../../App';
import { TUserRating } from '../../services/server/types';
import firstPlaceIcon from '../../assets/img/first_place.svg';
import snowman1 from '../../assets/img/HNY/snowman1.svg';
import snowman2 from '../../assets/img/HNY/snowman2.svg';
import './Leaderboard.scss';
import SnowEffect from '../Lobby/SnowEffect';

const Leaderboard: React.FC<IBasePage> = ({ setPage }) => {
    const store = useContext(StoreContext);
    const server = useContext(ServerContext);
    const user = store.getUser();
    const [rating, setRating] = useState<TUserRating[]>([]);

    const [player] = useState({
        name: user?.name,
        balance: user?.balance
    });

    useEffect(() => {
        const fetchRating = async () => {
            const result = await server.getRatingTable();
            if (result && result.rating) {
                setRating(result.rating);
            }
        };

        fetchRating();
    }, [server]);

    const handleBackToLobby = () => {
        setPage(PAGES.LOBBY);
    };

    const getRankIcon = (index: number) => {
        return `${index + 1}.`;
    };

    return (
        <div className="leaderboard-container">
            <SnowEffect />
            <header className="leaderboard-header">
                <div className="leaderboard-header-left">
                    <button className="leaderboard-back-to-lobby-button" onClick={handleBackToLobby}>
                        <span className="leaderboard-arrow">&lt;</span> Назад в лобби
                    </button>
                </div>

                <div className="leaderboard-header-right">
                    <span className="leaderboard-player-name">{player.name}</span>
                </div>
            </header>

            <main className="leaderboard-main-content">
                <div className="leaderboard-title-section">
                    <h1 className="leaderboard-page-title">Таблица лидеров</h1>
                    
                    <div className="leaderboard-logo">
                        <span className="leaderboard-logo-casino">CASIN</span>
                        <span className="leaderboard-logo-ochko">OCHKO</span>
                    </div>
                </div>

                <div className="leaderboard-table-wrapper">
                    <img src={snowman1} alt="Снеговик" className="leaderboard-snowman leaderboard-snowman-left" />
                    <div className="leaderboard-table-container">
                        {rating.length === 0 ? (
                            <div className="leaderboard-empty">Нет данных о рейтинге</div>
                        ) : (
                            <table className="leaderboard-table">
                                <thead>
                                    <tr>
                                        <th className="leaderboard-th-rank">Место</th>
                                        <th className="leaderboard-th-name">Игрок</th>
                                        <th className="leaderboard-th-balance">Баланс</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rating.map((user, index) => (
                                        <tr 
                                            key={user.id} 
                                            className={user.id === store.getUser()?.id ? 'leaderboard-current-user' : ''}
                                        >
                                            <td className="leaderboard-td-rank">
                                                <span className="leaderboard-rank-icon">{getRankIcon(index)}</span>
                                            </td>
                                            <td className="leaderboard-td-name">{user.name}</td>
                                            <td className="leaderboard-td-balance">${user.balance.toLocaleString()}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>
                    <img src={snowman2} alt="Снеговик" className="leaderboard-snowman leaderboard-snowman-right" />
                </div>
            </main>
        </div>
    );
};

export default Leaderboard;

