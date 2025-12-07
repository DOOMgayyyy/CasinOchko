import md5 from 'md5';
import CONFIG from "../../config";
import Store from "../store/Store";
import { TAnswer, TError, TRoomResponse, TMessagesResponse, TUser, TUserStats, TRawUserStats, TRoomInfoResponse, TLeaderboardResponse, TGetLeaveRoomResponse, TTakeCardResponse} from "./types";

const { CHAT_TIMESTAMP, HOST } = CONFIG;
const GAME_TIMESTAMP = 1000; // 1 секунда для игрового loop

class Server {
    HOST = HOST;
    store: Store;
    chatInterval: NodeJS.Timeout | null = null;
    gameInterval: NodeJS.Timeout | null = null;
    showErrorCb: (error: TError) => void = () => {};

    constructor(store: Store) {
        this.store = store;
    }

    // посылает запрос и обрабатывает ответ
    private async request<T>(method: string, params: { [key: string]: string } = {}): Promise<T | null> {
        try {
            params.method = method;
            const token = this.store.getToken();
            if (token) {
                params.token = token;
            }
            const response = await fetch(`${this.HOST}/?${Object.keys(params).map(key => `${key}=${params[key]}`).join('&')}`);
            const answer: TAnswer<T> = await response.json();
            if (answer.result === 'ok' && answer.data) {
                return answer.data;
            }
            answer.error && this.setError(answer.error);
            return null;
        } catch (e) {
            console.log(e);
            this.setError({
                code: 9000,
                text: 'Unknown error',
            });
            return null;
        }
    }

    private setError(error: TError): void {
        this.showErrorCb(error);
    }

    showError(cb: (error: TError) => void) {
        this.showErrorCb = cb;
    }

    async login(email: string, password: string): Promise<boolean> {
        const rnd = Math.round(Math.random() * 100000);
        const passHash = md5(password);
        const hash = md5(`${passHash}${rnd}`);  
        const user = await this.request<TUser>('login', { email, hash, rnd: `${rnd}` });
        if (user) {
            this.store.setUser(user);
            return true;
        }
        return false;
    }

    async logout() {
        const result = await this.request<boolean>('logout');
        if (result) {
            this.store.clearUser();
        }
    }
    // async logout(): Promise<void> {
    //     const result = await this.request<boolean>('logout');
    //     if (result) {
    //         this.store.clearUser();
    //     }
    // }
    async updateUserName(newName: string): Promise<boolean> {
        // Вызываем серверный метод updateUserName, передавая новое имя
        const result = await this.request<boolean>('updateUserName', { newName });
        
        if (result) {
            // Если сервер вернул 'ok', обновляем имя локально в Store
            this.store.setUserName(newName); 
            return true;
        }
        return false;
    }


    async registration(email: string, password: string, name: string): Promise<boolean> {
        const passHash = md5(password);
        // 1. Ожидаем от сервера полный объект пользователя (TUser)
        const user = await this.request<TUser>('registration', { email, password: passHash, name });

        // 2. Если пользователь успешно создан и получен...
        if (user) {
            // 3. ...сохраняем его данные в store, чтобы он сразу вошел в систему
            this.store.setUser(user);
            return true;
        }
        // 4. Если что-то пошло не так, возвращаем false
        return false;
    }

    sendMessage(message: string, roomId?: number | null): void {
    const currentRoomId = roomId || this.store.getCurrentRoomId();
    if (currentRoomId) {
        this.request<{ hash: string }>('sendMessage', { 
            message, 
            room_id: String(currentRoomId) 
        });
    }
}

async getMessages(roomId?: number | null): Promise<TMessagesResponse | null> {
    const hash = this.store.getChatHash();
    const currentRoomId = roomId || this.store.getCurrentRoomId();
    
    if (!currentRoomId) {
        return null;
    }
    
    const result = await this.request<TMessagesResponse>('getMessages', { 
        hash, 
        room_id: String(currentRoomId) 
    });
    
    if (result) {
        this.store.setChatHash(result.hash);
        return result;
    }
    return null;
}

startChatMessages(cb: (hash: string) => void, roomId?: number | null): void {
    const currentRoomId = roomId || this.store.getCurrentRoomId();
    
    if (!currentRoomId) {
        console.error('No room ID for chat');
        return;
    }
    
    this.chatInterval = setInterval(async () => {
        const result = await this.getMessages(currentRoomId);
        if (result) {
            const { messages, hash } = result;
            this.store.addMessages(messages, currentRoomId);
            cb(hash);
        }
    }, CHAT_TIMESTAMP);
}

    stopChatMessages(): void {
        if (this.chatInterval) {
            clearInterval(this.chatInterval);
            this.chatInterval = null;
            this.store.clearMessages();
        }
    }
    
    async createPrivateRoom(): Promise<TRoomResponse | null> {
        return await this.request<TRoomResponse>('createPrivateRoom');
    }
    
    async joinPrivateRoom(code: string): Promise<TRoomResponse | null> {
        return await this.request<TRoomResponse>('joinPrivateRoom', { code });
    }
    
    async quickStart(): Promise<TRoomResponse | null> {
        return await this.request<TRoomResponse>('quickStart');
    }

    async getUserStat(): Promise<TUserStats | null> {
    const result = await this.request<{ stats: TRawUserStats }>('getUserStat');


    

    if (!result) {
        return null;
    }

    const { totalplayed, totalwin, totalbalance, totalhours } = result.stats;

    return {
        totalGames: Number(totalplayed),
        totalWins: Number(totalwin),
        totalMoney: Number(totalbalance),
        totalHours: totalhours ? Number(totalhours) : 0
    };
    }

    // Метод для вычисления счёта карт через сервер
    async calculateUserScore(cardsString: string): Promise<number | null> {
        // Отправляем строку карт напрямую на сервер
        const result = await this.request<{ score: number }>('calculateScore', { 
            cards: cardsString 
        });
        
        if (result && typeof result.score === 'number') {
            return result.score;
        }
        
        return null;
    }


    async addBalance(amount: number): Promise<{ ok: boolean; newBalance?: number }> {
        const result = await this.request<{ balance: number }>('addBalance', { amount: String(amount) });

        if (result && typeof result.balance === 'number') {
            const user = this.store.getUser();
            if (user) {
                this.store.setUser({ ...user, balance: result.balance });
            }
            return { ok: true, newBalance: result.balance };
        }

        return { ok: false };
    }


    async getUserBalance(): Promise<number | null> {
        // 1. Изменяем ожидаемый тип: balance может быть строкой ИЛИ числом
        const result = await this.request<{ balance: number | string }>('getUserBalance');

        // 2. Проверяем, что balance существует (как строка или число)
        if (result && (typeof result.balance === 'number' || typeof result.balance === 'string')) {

            // 3. Принудительно конвертируем в число
            const numericBalance = Number(result.balance);

            // 4. Проверяем, что конвертация прошла успешно (не NaN)
            if (!isNaN(numericBalance)) {
                const user = this.store.getUser();
                if (user) {
                    // Сохраняем в store уже число
                    this.store.setUser({ ...user, balance: numericBalance });
                }
                // 5. Возвращаем ЧИСЛО
                return numericBalance;
            }
        }

        // Если проверка не удалась, возвращаем null
        return null;
    }

    // Получает информацию о комнате
    async getInfoRoom(roomId: number): Promise<TRoomInfoResponse | null> {
        const hash = this.store.getRoomHash();
        const result = await this.request<TRoomInfoResponse>('getInfoRoom', { 
            room_id: String(roomId), 
            hash 
        });
        if (result) {
            this.store.setRoomHash(result.hash);
            return result;
        }
        return null;
    }

    // Запустить обновление состояния игры
    startGameLoop(roomId: number, cb: (roomInfo: TRoomInfoResponse) => void): void {
        this.stopGameLoop();

        // Запускаем новый loop
        this.gameInterval = setInterval(async () => {
            const result = await this.getInfoRoom(roomId);
            if (result) {
                cb(result);
            }
        }, GAME_TIMESTAMP);
    }

   
    // Остановить game loop
    stopGameLoop(): void {
        if (this.gameInterval) {
            clearInterval(this.gameInterval);
            this.gameInterval = null;
            this.store.clearRoomHash();
        }
    }

    // Получить таблицу рейтинга
    async getRatingTable(): Promise<TLeaderboardResponse | null> {
        return await this.request<TLeaderboardResponse>('getRatingTable');
    }

    // Покинуть комнату
    async leaveRoom(): Promise<TGetLeaveRoomResponse | null> {
        return await this.request<TGetLeaveRoomResponse>('leaveRoom');
    }

    // Сделать ставку
    async makeBet(roomId: number, amount: number): Promise<{ success: boolean } | null> {
        const result = await this.request<{ success: boolean }>('makeBet', { 
            room_id: String(roomId),
            amount: String(amount)
        });
        
        if (result && result.success) {
            // Обновление баланса пользователя после успешной ставки
            const user = this.store.getUser();
            if (user) {
                this.store.setUser({ ...user, balance: user.balance - amount });
            }
        }
        
        return result;
    }

    // Взять карту
    async takeUserCard(roomId: number): Promise<TTakeCardResponse| null> {
        const result = await this.request<{ success: boolean, card?: string, shouldPass?: boolean }>('takeUserCard', { 
            room_id: String(roomId)
        });
        
        return result;
    }
} 

export default Server;