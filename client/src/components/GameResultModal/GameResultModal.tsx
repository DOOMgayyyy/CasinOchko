import React from 'react';
import './GameResultModal.scss';
import bombIcon from '../../assets/img/icons/bomb.png';
import cupIcon from '../../assets/img/icons/cup.png';
import drawIcon from '../../assets/img/icons/draw.png';

export type GameResultStatus = 'bust' | 'push' | 'blackjack' | 'win' | 'lose';

interface GameResultModalProps {
    status: GameResultStatus;
    betAmount: number;
    onClose: () => void;
}

// Функция для вычисления выигрыша на основе статуса и ставки
const calculateWinAmount = (status: GameResultStatus, betAmount: number): number => {
    switch (status) {
        case 'blackjack':
            return betAmount * 2.5; // Выплата 3:2
        case 'win':
            return betAmount * 2; // Выплата 2:1
        case 'push':
            return betAmount; // Возврат ставки
        case 'bust':
        case 'lose':
            return 0; // Нет выигрыша
        default:
            return 0;
    }
};

const GameResultModal: React.FC<GameResultModalProps> = ({ status, betAmount, onClose }) => {
    const winAmount = calculateWinAmount(status, betAmount);
    const getResultInfo = () => {
        switch (status) {
            case 'bust':
                return {
                    title: 'ПЕРЕБОР!',
                    iconSrc: bombIcon,
                    message: 'Вы набрали больше 21 очка',
                    color: '#FF2220',
                    resultText: 'Проигрыш'
                };
            case 'push':
                return {
                    title: 'НИЧЬЯ!',
                    iconSrc: drawIcon,
                    message: 'Вы и дилер набрали одинаковое количество очков',
                    color: '#FFB800',
                    resultText: 'Возврат ставки'
                };
            case 'blackjack':
                return {
                    title: 'БЛЭКДЖЕК!',
                    iconSrc: cupIcon,
                    message: 'Поздравляем! Вы получили блэкджек!',
                    color: '#FFB800',
                    resultText: 'Выплата 3:2'
                };
            case 'win':
                return {
                    title: 'ПОБЕДА!',
                    iconSrc: cupIcon,
                    message: 'Вы победили дилера!',
                    color: '#27C213',
                    resultText: 'Выплата 2:1'
                };
            case 'lose':
                return {
                    title: 'ПРОИГРЫШ',
                    iconSrc: bombIcon,
                    message: 'Дилер набрал больше очков',
                    color: '#FF2220',
                    resultText: 'Проигрыш'
                };
            default:
                return {
                    title: 'РЕЗУЛЬТАТ',
                    iconSrc: drawIcon,
                    message: '',
                    color: '#8F8F8F',
                    resultText: ''
                };
        }
    };

    const resultInfo = getResultInfo();

    return (
        <div className="game-result-modal-overlay" onClick={onClose}>
            <div className="game-result-modal" onClick={(e) => e.stopPropagation()}>
                <div className="game-result-modal-content">
                    <button className="game-result-close-btn" onClick={onClose}>×</button>
                    
                    <div className="game-result-icon">
                        <img src={resultInfo.iconSrc} alt={resultInfo.title} />
                    </div>
                    
                    <h2 className="game-result-title" style={{ color: resultInfo.color }}>
                        {resultInfo.title}
                    </h2>
                    
                    <p className="game-result-message">{resultInfo.message}</p>
                    
                    <div className="game-result-info">
                        <div className="game-result-row">
                            <span className="game-result-label">Результат:</span>
                            <span className="game-result-value" style={{ color: resultInfo.color }}>
                                {resultInfo.resultText}
                            </span>
                        </div>
                        
                        {betAmount !== undefined && (
                            <div className="game-result-row">
                                <span className="game-result-label">Ваша ставка:</span>
                                <span className="game-result-value">${betAmount}</span>
                            </div>
                        )}
                        
                        {winAmount !== undefined && winAmount > 0 && (
                            <div className="game-result-row highlight">
                                <span className="game-result-label">Выигрыш:</span>
                                <span className="game-result-value win-amount" style={{ color: resultInfo.color }}>
                                    +${winAmount}
                                </span>
                            </div>
                        )}
                    </div>
                    
                    <button 
                        className="game-result-continue-btn" 
                        onClick={onClose}
                        style={{ borderColor: resultInfo.color, color: resultInfo.color }}
                    >
                        Продолжить
                    </button>
                </div>
            </div>
        </div>
    );
};

export default GameResultModal;

