import React, { useContext, useRef } from 'react';
import { ServerContext } from '../../../App';

import './ChangeName.scss';

interface ChangeNameProps {
  currentName: string;
  onClose: () => void;
  onSuccess: () => void;
}

const ChangeName: React.FC<ChangeNameProps> = ({ currentName, onClose, onSuccess }) => {
  const server = useContext(ServerContext);
  const newNameRef = useRef<HTMLInputElement>(null);
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    const showCustomError = (message: string, code: number) => {
      server.showErrorCb({
        code,
        text: message
      });
    };

    const ERROR_CODES = {
      EMPTY_NAME: 1001,
      SAME_NAME: 1002,
      UPDATE_FAILED: 1003
    };

    if (newNameRef.current) {
      const newName = newNameRef.current.value;
      
      if (!newName.trim()) {
        showCustomError("Введите новое имя", ERROR_CODES.EMPTY_NAME);;
        return;
      }
      
      if (newName.trim() === currentName) {
        showCustomError("Новое имя должно отличаться от текущего", ERROR_CODES.SAME_NAME);
        return;
      }
      
      const success = await server.updateUserName(newName.trim());
      if (success) {
        console.log("Имя успешно обновлено");
        onSuccess(); 
        onClose();  
      } else {
        showCustomError("Ошибка обновления имени", ERROR_CODES.UPDATE_FAILED);
      }
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
              ref={newNameRef}
              className="input"
              id="newName"
              type="text"
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
