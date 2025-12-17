import React from 'react';
import { TPlayer } from '../../services/server/types';
import './CurrentTurnInfo.scss';

interface CurrentTurnInfoProps {
    players: TPlayer[];
    currentMemberId: number | null;
}

const CurrentTurnInfo: React.FC<CurrentTurnInfoProps> = ({
    players,
    currentMemberId
}) => {
    // Находим игрока, чей сейчас ход
    const currentPlayer = currentMemberId !== null 
        ? players.find(player => player.memberId === currentMemberId)
        : null;

    return (
        <div className="current-turn-info">
            <span className="current-turn-label">Текущий ход:</span>
            <span className="current-turn-player">
                {currentPlayer ? currentPlayer.name : '—'}
            </span>
        </div>
    );
};

export default CurrentTurnInfo;

