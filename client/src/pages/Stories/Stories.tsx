import React, { useState, useEffect, useRef } from 'react';
import { IBasePage, PAGES } from '../PageManager';
import './Stories.scss';

interface Story {
    number: string | number;
    description: string;
}

const stories: Story[] = [    
    { number: '0', description: 'созвонов с полным составом команды' },
    { number: '1', description: 'раз удалялась дев ветка (главное, что она осталась жива)' },
    { number: '2-', description: '-дцать процентов пары обсуждали проект, остальное — обсирали код, дизайн и тимлида' },
    { number: '3', description: 'раза Трусов произнёс фразу "что у вас тут напидорашено?", постигая глубины нашего кода' },
    { number: '4', description: 'раза тимлиду приходила идея переписать проект с нуля (все 4 раза закончились плохо)' },
    { number: '5', description: 'пар мы мучились с запуском проекта на компе в удгу (тот ещё доисторический аппарат)' },
    { number: '6', description: 'раз фраза «потом перепишем» звучала как рабочий план' },
    { number: '7', description: 'раз возникало ощущение, что фронт и бэк живут в разных вселенных' },
    { number: '8', description: 'раз код считался рабочим, потому что «он же компилируется»' },
    { number: '9', description: 'сообщений в чате проекта ушло на выяснение, где вообще лежит проект' },
    { number: '10', description: 'процентов времени делали казино, 90% — делали вид' },
    { number: '11', description: 'раз слово «архитектура» использовалось как оправдание бардака' },
    { number: '12', description: 'раз хотелось всё удалить и сделать вид, что так и было' },
    { number: '13', description: 'раз «у меня работает» считалось тестированием' },
    { number: '14', description: 'лбов упорно трудились на проектом и пытались что-то выдавить в репозиторий (ну получилось ведь что-то, да?)' },

];

const STORY_DURATION = 5000; // 3 секунды на сторис

const Stories: React.FC<IBasePage> = ({ setPage }) => {
    const [currentIndex, setCurrentIndex] = useState(0);
    const [progress, setProgress] = useState(0);
    const isFinished = useRef(false);

    useEffect(() => {
        // Если уже закончили показ всех сторис, не запускаем интервал
        if (isFinished.current) return;

        const interval = setInterval(() => {
            setProgress((prev) => {
                const newProgress = prev + (100 / (STORY_DURATION / 50));
                if (newProgress >= 100) {
                    return 100;
                }
                return newProgress;
            });
        }, 50);

        return () => clearInterval(interval);
    }, [currentIndex]);

    useEffect(() => {
        if (progress >= 100 && !isFinished.current) {
            handleNext();
        }
    }, [progress]);

    const handleNext = () => {
        if (currentIndex < stories.length - 1) {
            setCurrentIndex((prev) => prev + 1);
            setProgress(0);
        } else {
            // Закончились сторис, возвращаемся в лобби
            isFinished.current = true;
            setPage(PAGES.LOBBY);
        }
    };

    const handleClick = () => {
        if (!isFinished.current) {
            handleNext();
        }
    };

    const handleClose = () => {
        isFinished.current = true;
        setPage(PAGES.LOBBY);
    };

    return (
        <div className="stories-container" onClick={handleClick}>
            <button className="stories-close-btn" onClick={(e) => {
                e.stopPropagation();
                handleClose();
            }}>
                ×
            </button>

            {/* Прогресс бары */}
            <div className="stories-progress-bars">
                {stories.map((_, index) => (
                    <div key={index} className="progress-bar-wrapper">
                        <div
                            className={`progress-bar ${
                                index < currentIndex ? 'completed' : ''
                            } ${index === currentIndex ? 'active' : ''}`}
                            style={{
                                width: index === currentIndex ? `${progress}%` : 
                                       index < currentIndex ? '100%' : '0%'
                            }}
                        />
                    </div>
                ))}
            </div>

            {/* Контент сторис */}
            <div className="stories-content">
                <div className="stories-number">{stories[currentIndex].number}</div>
                <div className="stories-description">{stories[currentIndex].description}</div>
            </div>

            {/* Подсказка */}
            <div className="stories-hint">Нажмите, чтобы пропустить</div>
        </div>
    );
};

export default Stories;

