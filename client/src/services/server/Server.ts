import md5 from 'md5';
import CONFIG from "../../config";
import Store from "../store/Store";
import { TAnswer, TError, TPrivateRoomResponse, TMessagesResponse, TUser, TUserStats, TRawUserStats } from "./types";


const { CHAT_TIMESTAMP, HOST } = CONFIG;

class Server {
    HOST = HOST;
    store: Store;
    chatInterval: NodeJS.Timeout | null = null;
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
        // Убираем passHash = md5(password)
        // Отправляем сырой password
        const user = await this.request<TUser>('registration', { email, password, name });

        if (user) {
            this.store.setUser(user);
            return true;
        }
        
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
        const result = await this.request<{ balance: number }>('getUserBalance');
        if (result && typeof result.balance === 'number') {
            const user = this.store.getUser();
            if (user) {
                this.store.setUser({ ...user, balance: result.balance });
            }
            return result.balance;
        }
        return null;
    }
} 

export default Server;