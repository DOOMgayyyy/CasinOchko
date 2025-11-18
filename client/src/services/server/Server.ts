import md5 from 'md5';
import CONFIG from "../../config";
import Store from "../store/Store";
import { TAnswer, TError, TPrivateRoomResponse, TMessagesResponse, TUser, TUserStats, TRawUserStats, TQuickStartResponse, TRoomInfoResponse, TJoinPrivateRoomResponse } from "./types";

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

    sendMessage(message: string): void {
        this.request<boolean>('sendMessage', { message });
    }

    async getMessages(): Promise<TMessagesResponse | null> {
        const hash = this.store.getChatHash();
        const result = await this.request<TMessagesResponse>('getMessages', { hash });
        if (result) {
            this.store.setChatHash(result.hash);
            return result;
        }
        return null;
    }

    startChatMessages(cb: (hash: string) => void): void {
        this.chatInterval = setInterval(async () => {
            const result = await this.getMessages();
            if (result) {
                const { messages, hash } = result;
                this.store.addMessages(messages);
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
    
    async createPrivateRoom(): Promise<TPrivateRoomResponse | null> {
        return await this.request<TPrivateRoomResponse>('createPrivateRoom');
    }
    
    async joinPrivateRoom(code: string): Promise<TPrivateRoomResponse | null> {
    return await this.request<TPrivateRoomResponse>('joinPrivateRoom', { code });
}
    async quickStart(): Promise<TQuickStartResponse | null> {
        return await this.request<TQuickStartResponse>('quickStart');
    }

    async getUserStat(): Promise<TUserStats | null> {
    const result = await this.request<{ stats: TRawUserStats }>('getUserStat');

    if (!result) {
        return null;
    }

    const { total_played, total_win, total_balance, total_hours } = result.stats;

    return {
        totalGames: Number(total_played),
        totalWins: Number(total_win),
        totalMoney: Number(total_balance),
        totalHours: total_hours ? Number(total_hours) : 0
    };
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
            // Вызываем callback только если есть изменения
            if (result && result.changed) {
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
} 

export default Server;