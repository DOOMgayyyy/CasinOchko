import React from 'react';
import { TPlayer } from '../../services/server/types';

interface GameTableProps {
    players: TPlayer[];
    playersScores: { [memberId: number]: number };
    myMemberId: number | null;
    currentPlayerId: number | null;
    getCardImage: (card: string) => string;
}

const GameTable: React.FC<GameTableProps> = ({
    players,
    playersScores,
    myMemberId,
    currentPlayerId,
    getCardImage
}) => {
    const positions = ['left-top', 'left-middle', 'left-bottom', 'right-bottom', 'right-middle', 'right-top'];

    return (
        <>
            {/* надписи с инфой игроков за столом */}
            <div className="players">
                {players.map((player, index) => {
                    const position = positions[index] || 'left-top';
                    const isCurrentPlayer = myMemberId !== null && player.memberId === myMemberId;
                    const isActiveTurn = currentPlayerId === player.memberId;
                    const score = playersScores[player.memberId] ?? 0;
                    
                    return (
                        <div 
                            className={`player-slot ${position} ${isCurrentPlayer ? 'active-player' : ''} ${isActiveTurn ? 'current-turn' : ''}`} 
                            key={player.memberId}
                        >
                            <span className="name">{player.name}</span>
                            <span className="balance">${player.balance}</span>
                            <span className="score">{score}</span>
                            {player.bet > 0 && <span className="bet">Ставка: ${player.bet}</span>}
                        </div>
                    );
                })}
            </div>

            {/* карты игроков на столе */}
            <div className="table-cards-container">
                {players.map((player, index) => {
                    const position = positions[index] || 'left-top';
                    const playerCards = player.cards ? player.cards.match(/.{1,2}/g) : [];
                    
                    return (
                        <div 
                            className={`table-player-cards ${position}`} 
                            key={`table-cards-${player.memberId}`}
                        >
                            {playerCards && playerCards.length > 0 ? (
                                playerCards.map((card, cardIndex) => {
                                    const cardImage = getCardImage(card);
                                    return (
                                        <div 
                                            className="table-card" 
                                            key={cardIndex}
                                            style={{ animationDelay: `${cardIndex * 0.2}s` }}
                                        >
                                            {cardImage ? (
                                                <img src={cardImage} alt={card} />
                                            ) : (
                                                <span>{card}</span>
                                            )}
                                        </div>
                                    );
                                })
                            ) : null}
                        </div>
                    );
                })}
            </div>
        </>
    );
};

export default GameTable;

