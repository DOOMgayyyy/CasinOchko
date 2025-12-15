import React from 'react';

interface DealerInfoProps {
    dealerCards: string;
    dealerScore: number;
    getCardImage: (card: string) => string;
}

const DealerInfo: React.FC<DealerInfoProps> = ({
    dealerCards,
    dealerScore,
    getCardImage
}) => {
    return (
        <>
            {/* дилер */}
            <div className='diller-slot'>
                <span className="diller-name">Дилер: </span>
                {dealerCards && dealerCards.length > 0 && (
                    <span className="diller-score">{dealerScore}</span>
                )}
            </div>
            
            {/* карты дилера */}
            <div className="dealer-cards">
                {dealerCards && dealerCards.length > 0 ? (
                    dealerCards.match(/.{1,2}/g)?.map((card, index) => {
                        const cardImage = getCardImage(card);
                        return (
                            <div 
                                className="dealer-card" 
                                key={index}
                                style={{ animationDelay: `${index * 0.2}s` }}
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
        </>
    );
};

export default DealerInfo;

