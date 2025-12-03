import React, { useEffect, useState } from 'react';
import './SnowEffect.scss';

interface Snowflake {
    id: number;
    left: number;
    animationDuration: number;
    animationDelay: number;
    size: number;
    opacity: number;
}

const SnowEffect: React.FC = () => {
    const [snowflakes, setSnowflakes] = useState<Snowflake[]>([]);

    useEffect(() => {
        // Определяем количество снежинок в зависимости от производительности устройства
        // Для слабых устройств используем меньше снежинок
        const isLowEndDevice = 
            navigator.hardwareConcurrency && navigator.hardwareConcurrency <= 2 ||
            /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        
        const count = isLowEndDevice ? 20 : 30; // Уменьшено с 50 до 20-30
        const newSnowflakes: Snowflake[] = [];

        for (let i = 0; i < count; i++) {
            newSnowflakes.push({
                id: i,
                left: Math.random() * 100, // Случайная позиция по горизонтали (0-100%)
                animationDuration: 4 + Math.random() * 4, // Длительность падения 4-8 секунд (немного медленнее)
                animationDelay: Math.random() * 5, // Задержка начала анимации 0-5 секунд
                size: 4 + Math.random() * 6, // Размер 4-10px
                opacity: 0.4 + Math.random() * 0.6, // Прозрачность 0.4-1.0
            });
        }

        setSnowflakes(newSnowflakes);
    }, []);

    return (
        <div className="snow-container">
            {snowflakes.map((snowflake) => (
                <div
                    key={snowflake.id}
                    className="snowflake"
                    style={{
                        left: `${snowflake.left}%`,
                        width: `${snowflake.size}px`,
                        height: `${snowflake.size}px`,
                        animationDuration: `${snowflake.animationDuration}s`,
                        animationDelay: `${snowflake.animationDelay}s`,
                        opacity: snowflake.opacity,
                    }}
                >
                    ❄
                </div>
            ))}
        </div>
    );
};

export default SnowEffect;

