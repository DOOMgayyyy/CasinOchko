import React, { useState, useRef, useEffect } from 'react';

interface BetModalProps {
    isOpen: boolean;
    userBalance: number;
    minBet: number;
    onPlace: (betAmount: number) => void;
    onCancel: () => void;
}

const BetModal: React.FC<BetModalProps> = ({
    isOpen,
    userBalance,
    minBet,
    onPlace,
    onCancel
}) => {
    const [currentBet, setCurrentBet] = useState(0);
    const betInputRef = useRef<HTMLInputElement>(null);

    // Сброс ставки при открытии модального окна
    useEffect(() => {
        if (isOpen) {
            setCurrentBet(0);
        }
    }, [isOpen]);

    const handleBetIncrease = () => {
        const newBet = Math.min(currentBet + 50, userBalance);
        setCurrentBet(newBet);
    };

    const handleBetDecrease = () => {
        const newBet = Math.max(currentBet - 50, minBet);
        setCurrentBet(newBet);
    };

    const handleBetInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value;
        // Только числа или пустая строка
        if (value === '' || /^\d+$/.test(value)) {
            if (value === '') {
                setCurrentBet(0);
                return;
            }
            
            const numValue = parseInt(value, 10);
            if (numValue > userBalance) {
                setCurrentBet(userBalance);
            } else if (numValue < 0) {
                setCurrentBet(0);
            } else {
                setCurrentBet(numValue);
            }
        }
    };

    const handlePlaceBet = () => {
        if (currentBet === 0 || currentBet < minBet || currentBet > userBalance) {
            return;
        }
        onPlace(currentBet);
        setCurrentBet(0);
    };

    const handleCancel = () => {
        setCurrentBet(0);
        onCancel();
    };

    if (!isOpen) {
        return null;
    }

    return (
        <div className="bet-modal-overlay" onClick={handleCancel}>
            <div className="bet-modal" onClick={(e) => e.stopPropagation()}>
                <div className="bet-modal-content">
                    <h2 className="bet-modal-title">Сделать ставку</h2>
                    
                    <div className="bet-balance">
                        <span className="bet-balance-label">Ваш баланс:</span>
                        <span className="bet-balance-amount">${userBalance}</span>
                    </div>

                    <div className="bet-controls">
                        <button 
                            className="bet-control-button bet-decrease" 
                            onClick={handleBetDecrease}
                            disabled={currentBet === minBet}
                        >
                            - 50$
                        </button>
                        <input
                            ref={betInputRef}
                            type="text"
                            className="bet-amount-input"
                            value={currentBet === 0 ? '' : currentBet}
                            onChange={handleBetInputChange}
                            placeholder="0"
                            maxLength={10}
                        />
                        <button 
                            className="bet-control-button bet-increase" 
                            onClick={handleBetIncrease}
                            disabled={currentBet + 50 > userBalance}
                        >
                            + 50$
                        </button>
                    </div>

                    <div className="bet-modal-buttons">
                        <button 
                            className="bet-action-button bet-cancel" 
                            onClick={handleCancel}
                        >
                            <span className="bet-arrow">&lt;</span> отмена
                        </button>
                        <button 
                            className="bet-action-button bet-place" 
                            onClick={handlePlaceBet}
                            disabled={currentBet < minBet || currentBet > userBalance}
                        >
                            поставить <span className="bet-arrow">&gt;</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default BetModal;

