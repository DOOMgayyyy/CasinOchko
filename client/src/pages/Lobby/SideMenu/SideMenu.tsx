import React, { useState } from 'react';
import './SideMenu.css';
import ChangeName from '../ChangeName/ChangeName';

export interface SideMenuProps {
    player: {
        name: string;
        balance: number;
    };
    stats: {
        totalGames: number;
        totalWins: number;
        totalMoney: number;
        totalHours: number;
        
    };
    onClose: () => void;
    onEditName: () => void;
    onShowRules: () => void;
    onShowAuthors: () => void;
    onLogout: () => void;
}

const SideMenu: React.FC<SideMenuProps> = ({ 
    player, 
    stats, 
    onClose, 
    onEditName,
    onShowRules, 
    onShowAuthors, 
    onLogout 
}) => {
    const [showChangeName, setShowChangeName] = useState(false);

    const handleEditNameClick = () => {
        setShowChangeName(true);
    };

    const handleChangeNameSuccess = () => {
        onEditName();
        setShowChangeName(false);
    };
    return (
        <div className="side-menu-overlay" onClick={onClose}>
            <div className="side-menu" onClick={(e) => e.stopPropagation()}>
               
                {/* Верхняя часть - имя игрока */}
                <div className="player-info-section">
                    {showChangeName ? (
                        <ChangeName
                            currentName={player.name}
                            onClose={() => setShowChangeName(false)}
                            onSuccess={handleChangeNameSuccess}
                        />
                    ) : (
                        <div className="player-name-edit">
                            <span className="player-name-value">{player.name}</span>
                            <button className="edit-name-btn" onClick={handleEditNameClick}>&lt; изменить</button>
                        </div>
                    )}
                </div>

                {/* Статистика */}
                <div className="stats-section">
                    <h3>Статистика: </h3>
                    <div className="stats-grid">
                        <div className="stat-item">
                            <span className="stat-label">Всего игр:</span>
                            <span className="stat-value">{stats.totalGames}</span>
                        </div>
                        <div className="stat-item">
                            <span className="stat-label">Победы:</span>
                            <span className="stat-value">{stats.totalWins}</span>
                        </div>
                        <div className="stat-item">
                            <span className="stat-label">Общая сумма:</span>
                            <span className="stat-value">${stats.totalMoney}</span>
                        </div>
                        <div className="stat-item">
                            <span className="stat-label">Всего часов:</span>
                            <span className="stat-value">{stats.totalHours}ч</span>
                        </div>
                    </div>
                </div>

                <div className="menu-links">
                     <div className="menu-divider"></div> 
                    <button className="menu-link" onClick={onShowRules}>
                        &lt;правила
                    </button>
                    <button className="menu-link" onClick={onShowAuthors}>
                        &lt;авторы
                    </button>
                    <button className="menu-link" onClick={onLogout}>
                        &lt;выйти
                    </button>
</div>
            </div>
        </div>
    );
};

export default SideMenu;