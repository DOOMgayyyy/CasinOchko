import { TMessages, TUser } from "../server/types";

const TOKEN = 'token';
const USER_DATA = 'userData';

class Store {
    user: TUser | null = null;
    messages: TMessages = [];
    chatHash: string = 'empty chat hash';
    roomHash: string = 'empty room hash';
    currentRoomId: number | null = null;
    roomCode: string | null = null;

    setToken(token: string): void {
        localStorage.setItem(TOKEN, token);
    }

    getToken(): string | null {
        return localStorage.getItem(TOKEN);
    }

    setUser(user: TUser): void {
        const { token } = user;
        this.setToken(token);
        this.user = user;
        // Сохраняем данные пользователя в localStorage
        localStorage.setItem(USER_DATA, JSON.stringify(user));
    }

    getUser(): TUser | null {
        // Если пользователь есть в памяти, возвращаем его
        if (this.user) {
            return this.user;
        }
        // Иначе пытаемся восстановить из localStorage
        const userData = localStorage.getItem(USER_DATA);
        if (userData) {
            this.user = JSON.parse(userData);
            return this.user;
            
        }
        return null;
    }

    clearUser(): void {
        this.user = null;
        this.setToken('');
        localStorage.removeItem(USER_DATA);
    }

    private roomMessages: Map<number, TMessages> = new Map();

addMessages(messages: TMessages, roomId?: number): void {
    const targetRoomId = roomId || this.currentRoomId;
    
    if (!targetRoomId) {
        console.error('No room ID for messages');
        return;
    }
    
    const existingMessages = this.roomMessages.get(targetRoomId) || [];
    // Объединяем старые и новые сообщения, убираем дубликаты
    const combinedMessages = [...existingMessages, ...messages];
    
    // Фильтруем дубликаты по автору, сообщению и времени
    const uniqueMessages = combinedMessages.filter((message, index, self) =>
        index === self.findIndex((m) => 
            m.author === message.author && 
            m.message === message.message && 
            m.created === message.created
        )
    );
    
    this.roomMessages.set(targetRoomId, uniqueMessages);
}

    getMessages(roomId?: number | null): TMessages {
        const targetRoomId = roomId || this.currentRoomId;
        
        if (!targetRoomId) {
            return [];
        }
        
        return this.roomMessages.get(targetRoomId) || [];
    }

    clearMessages(roomId?: number): void {
        if (roomId) {
            this.roomMessages.delete(roomId);
        } else if (this.currentRoomId) {
            this.roomMessages.delete(this.currentRoomId);
        }
    }

    getChatHash(): string {
        return this.chatHash;
    }

    setChatHash(hash: string): void {
        this.chatHash = hash;
    }
    setUserName(newName: string): void {
        // Обновляем свойство name в текущем объекте пользователя
        if (this.user) {
            this.user.name = newName;
            // Сохраняем обновленные данные в localStorage
            localStorage.setItem(USER_DATA, JSON.stringify(this.user));
        }
    }

    getRoomHash(): string {
        return this.roomHash;
    }

    setRoomHash(hash: string): void {
        this.roomHash = hash;
    }

    clearRoomHash(): void {
        this.roomHash = 'empty room hash';
    }

    setCurrentRoomId(roomId: number | null): void {
        this.currentRoomId = roomId;
    }

    getCurrentRoomId(): number | null {
        return this.currentRoomId;
    }

    clearCurrentRoomId(): void {
        this.currentRoomId = null;
    }

    setRoomCode(code: string | null): void {
        this.roomCode = code;
    }

    getRoomCode(): string | null {
        return this.roomCode;
    }

    clearRoomCode(): void {
        this.roomCode = null;
    }
}

export default Store;