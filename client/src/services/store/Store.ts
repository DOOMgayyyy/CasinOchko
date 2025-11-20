import { TMessages, TUser } from "../server/types";

const TOKEN = 'token';

class Store {
    user: TUser | null = null;
    messages: TMessages = [];
    chatHash: string = 'empty chat hash';
    roomHash: string = 'empty room hash';
    currentRoomId: number | null = null;

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
    }

    getUser(): TUser | null {
        return this.user;
    }

    clearUser(): void {
        this.user = null;
        this.setToken('');
    }

    addMessages(messages: TMessages): void {
        // TODO сделать, чтобы работало вот так
        //this.messages.concat(messages);
        // а вот это - плохой код!
        if (messages?.length) {
            this.messages = messages;
        }
    }

    getMessages(): TMessages {
        return this.messages;
    }

    clearMessages(): void {
        this.messages = [];
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
}

export default Store;