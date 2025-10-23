import React, { useState } from 'react';

import Preloader from './Preloader/Preloader';
import Login from './Login/Login';
import Chat from './Chat/Chat';
import GamePage from './Game/Game';
// import BlackjackPage from './GamePage/GamePage';
import NotFound from './NotFound/NotFound';
import Register from './Register/Register';
import Lobby from './Lobby/Lobby';
import Rules from './Rules/Rules';
export enum PAGES {
    PRELOADER,
    LOGIN,
    CHAT,
    GAME,
    NOT_FOUND,
    REGISTER,
    LOBBY,
    // Страницы, на которые можно перейти из лобби
    PRIVATE_ROOM,
    LEADERBOARD,
    QUICK_GAME,
    RULES,
    AUTHORS,
}

export interface IBasePage {
    setPage: (name: PAGES) => void
}

const PageManager: React.FC = () => {
    const [page, setPage] = useState<PAGES>(PAGES.LOGIN); 

    // Здесь можно будет добавить обработку для новых страниц
    // Например, отображение компонента таблицы лидеров
    // if (page === PAGES.LEADERBOARD) {
    //     return <Leaderboard setPage={setPage} />;
    // }

    return (
        <>
            {page === PAGES.PRELOADER && <Preloader setPage={setPage} />}
            {page === PAGES.LOGIN && <Login setPage={setPage} />}
            {page === PAGES.REGISTER && <Register setPage={setPage} />}
            {page === PAGES.CHAT && <Chat setPage={setPage} />}
            {page === PAGES.LOBBY && <Lobby setPage={setPage} />}
            {page === PAGES.GAME && <GamePage setPage={setPage} />}
            {page === PAGES.BLACKJACK && <BlackjackPage setPage={setPage} />}
            {page === PAGES.RULES && <Rules setPage={setPage} />}
            {page === PAGES.NOT_FOUND && <NotFound setPage={setPage} />}
        </>
    );
}

export default PageManager;