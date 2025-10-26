import React, { useState } from 'react';
import './ChangeName.scss';

interface ChangeNameProps {
  currentName: string;
  onClose: () => void;
  onSuccess: () => void;
}

const ChangeName: React.FC<ChangeNameProps> = ({ currentName, onClose, onSuccess }) => {
  const [newName, setNewName] = useState('');

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (newName.trim()) {
      onSuccess();
      onClose();
    }
  };

  return (
    <div className="change-name" >
      <div className="change-name-card">
        <h2 className="change-name-title">Смена ника</h2>
        
        <form onSubmit={handleSubmit}>
          <div className="field">
            <label className="label" htmlFor="newName">Новый ник:</label>
            <input
              className="input"
              id="newName"
              type="text"
              value={newName}
              onChange={(e) => setNewName(e.target.value)}
              placeholder="Введите новый ник"
            />
          </div>

          <div className="actions">
            <button type="button" className="btn-link" onClick={onClose}>
              <span className="arrow">&lt;</span>
              <span>отмена</span>
            </button>

            <button 
              type="submit" 
              className="btn-submit"
              disabled={!newName.trim()}
            >
              <span>сохранить</span>
              <span className="arrow">&gt;</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default ChangeName;
