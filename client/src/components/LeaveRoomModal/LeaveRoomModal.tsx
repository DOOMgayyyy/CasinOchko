import React from 'react';

interface LeaveRoomModalProps {
    isOpen: boolean;
    onLeave: () => void;
    onStay: () => void;
}

const LeaveRoomModal: React.FC<LeaveRoomModalProps> = ({
    isOpen,
    onLeave,
    onStay
}) => {
    if (!isOpen) {
        return null;
    }

    return (
        <div className="leave-room-modal-overlay" onClick={onStay}>
            <div className="leave-room-modal" onClick={(e) => e.stopPropagation()}>
                <div className="leave-room-modal-content">
                    <h2 className="leave-room-modal-title">Покинуть комнату?</h2>
                    <div className="leave-room-modal-buttons">
                        <button 
                            className="leave-room-button leave-button" 
                            onClick={onLeave}
                        >
                            Покинуть
                        </button>
                        <button 
                            className="leave-room-button stay-button" 
                            onClick={onStay}
                        >
                            Остаться
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default LeaveRoomModal;

