import React, { useMemo } from 'react';
import Store from './services/store/Store';
import Server from './services/server/Server';
import Popup from './components/Popup/Popup';
import PageManager from './pages/PageManager';

//import './App.scss';

export const StoreContext = React.createContext<Store>(null!);
export const ServerContext = React.createContext<Server>(null!);

const App: React.FC = () => {
    // Используем useMemo для создания store и server только один раз
    const store = useMemo(() => new Store(), []);
    const server = useMemo(() => new Server(store), [store]);

    return (
        <StoreContext.Provider value={store}>
            <ServerContext.Provider value={server}>
                <div className='app'>
                    <Popup />
                    <PageManager />
                </div>
            </ServerContext.Provider>
        </StoreContext.Provider>
    );
}

export default App;