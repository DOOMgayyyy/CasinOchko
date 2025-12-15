import React from 'react';

interface PlayerGameControlsProps {
    myCards: string;
    myScore: number;
    getCardImage: (card: string) => string;
    onHit: () => void;
    onStand: () => void;
    onDouble: () => void;
}

const PlayerGameControls: React.FC<PlayerGameControlsProps> = ({
    myCards,
    myScore,
    getCardImage,
    onHit,
    onStand,
    onDouble
}) => {
    return (
        <>
            {/* карты справа */}
            <div className="player-cards">
                <span className="your-cards-label">Ваши карты:</span>
            </div>
            <div className="my-cards">
                {myCards && myCards.length > 0 ? (
                    // Преобразуем строку карт в массив по 2 символа
                    myCards.match(/.{1,2}/g)?.map((card, index) => {
                        const cardImage = getCardImage(card);
                        return (
                            <div className="card" key={index}>
                                {cardImage ? (
                                    <img src={cardImage} alt={card} />
                                ) : (
                                    <span>{card}</span>
                                )}
                            </div>
                        );
                    })
                ) : (
                    <div className="card">
                        <span>Нет карт</span>
                    </div>
                )}
            </div>

            {/* кнопки управления игрой */}
            <div className='game-controls vertical'>
                <button className="game-button double-button" onClick={onDouble}>
                    Удвоить
                </button>
                <button className="game-button hit-button" onClick={onHit}>
                    Взять карту
                </button>
                <button className="game-button stand-button" onClick={onStand}>
                    Отказаться
                </button>
            </div>

            {/* отображение очков */}
            <div className='count-div'>
                <span className='count-span'>Ваши очки:</span>
                <span className='count-number'>{myScore}</span>
            </div>
        </>
    );
};

export default PlayerGameControls;

