import React, { useState } from 'react';

interface RoomCodeMessageProps {
    roomCode: string;
    onClose: () => void;
}

const RoomCodeMessage: React.FC<RoomCodeMessageProps> = ({
    roomCode,
    onClose
}) => {
    const [copySuccess, setCopySuccess] = useState(false);

    const handleCopyCode = async () => {
        if (roomCode) {
            await navigator.clipboard.writeText(roomCode);
            setCopySuccess(true);
            setTimeout(() => setCopySuccess(false), 2000);
        }
    };

    return (
        <div className="room-created-message">
            <div className="room-created-header">
                <span className="success-icon">✅</span>
                <span className="success-text">Комната создана!</span>
                <button className="close-message-btn" onClick={onClose}>×</button>
            </div>
            <div className="room-code-display">
                <div className="room-code-label">Код комнаты:</div>
                <div 
                    className="room-code-value clickable" 
                    onClick={handleCopyCode}
                    title="Нажмите, чтобы скопировать код">
                    {roomCode}
                </div>
            </div>
            {copySuccess && (
                <div className="copy-success">Код скопирован!</div>
            )}
        </div>
    );
};

export default RoomCodeMessage;

