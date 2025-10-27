import React, { useContext, useState } from 'react';
import { IBasePage, PAGES } from '../PageManager';
import { StoreContext, ServerContext } from '../../App';
import './Authors.scss';
import CasinochkoLogo from '../../assets/img/authors/CASINOCHKO.png';


const Authors: React.FC<IBasePage> = ({ setPage }) => {
    const store = useContext(StoreContext);
    const user = store.getUser();
    const [player] = useState({    
    name: user?.name || 'dev',
    balance: user?.balance || 99999,
  });
    const handleBackToLobby = () => {
        setPage(PAGES.LOBBY);
    };

    

    return (
         <div className="authors-container">
      <header className="authors-header">
         <div className="authors-header-left">
           <span className="authors-player-name">{player.name}</span>
        </div>

        <div className="authors-header-right">
                    <span className="authors-balance-text">Ваш баланс:</span>
                    <span className="authors-balance-amount">${player.balance}</span>
                </div>
      </header> 
       {/* Основной контент */}
              <main className="authors-main-content">
                <div className="authors-title-section">
                    {/* Заголовок Авторы */}
                    <h1 className="authors-page-title">Авторы</h1>
                    
                    {/* Логотип */}
                     <img src={CasinochkoLogo} alt="Casinochko" className="authors-logo-image" />
                </div>

                  {/* Три столбика */}
                <div className="authors-columns-container">
                    {/* Первый столбик */}
                    <div className="authors-column">
                        <div className="authors-team-block">
                            <div className="authors-role-title authors-team-leader-title">тимлидер</div>
                            <div className="authors-name">Самарин Михаил</div>
                        </div>

                        <div className="authors-team-block">
                            <div className="authors-role-title authors-designer-role">дизайнер</div>
                            <div className="authors-name">Бекмансуров Влад</div>
                        </div>

                        <div className="authors-team-block">
                            <div className="authors-role-title authors-analyst-role">аналитик</div>
                            <div className="authors-name">Уракова Юлия</div>
                        </div>
                        <button className="authors-back-btn" onClick={handleBackToLobby}>&lt;назад
    </button>
                    </div>
                    

                    {/* Второй столбик */}
                    <div className="authors-column">
                        <div className="authors-team-block">
                            <div className="authors-role-title authors-leader-title">руководитель проекта</div>
                            <div className="authors-name">Трусов Алексей</div>
                        </div>

                        <div className="authors-team-block">
                            <div className="authors-role-title authors-programmers-title">frontend-программисты</div>
                            <div className="authors-programmers-list">
                                <div className="authors-name">Мурин Виктор</div>
                                <div className="authors-name">Максимов Александр</div>
                                <div className="authors-name">Муртазина Камилла</div>
                                <div className="authors-name">Мамедова Айсун</div>
                                <div className="authors-name">Заборникова Юлия</div>
                            </div>
                        </div>
                    </div>

                    {/* Третий столбик */}
                    <div className="authors-column">
                        <div className="authors-team-block">
                            <div className="authors-role-title authors-programmers-title">backend-программисты</div>
                            <div className="authors-programmers-list">
                                <div className="authors-name">Моромов Никита</div>
                                <div className="authors-name">Хачатурян Глеб</div>
                                <div className="authors-name">Лотов Иван</div>
                                <div className="authors-name">Сметанин Егор</div>
                                <div className="authors-name">Хохряков Роман</div>
                                <div className="authors-name">Субботин Антон</div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </main>  
                
    </div>
    );
};

export default Authors;