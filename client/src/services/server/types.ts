export type TError = {
    code: number | string;
    text: string;
}

export type TAnswer<T> = {
    result: 'ok' | 'error';
    data?: T;
    error?: TError;
}

export type TUser = {
    id: number;
    email: string;
    name: string;
    balance: number;
    token: string;

}

export type TMessage = {
    message: string;
    author: string;
    created: string;
}

export type TMessages = TMessage[];
export type TMessagesResponse = {
    messages: TMessages;
    hash: string;
}

//Тип для ответов, связанных с комнатами
export type TRoomResponse = {
    id: number;                          
    type: 'private' | 'open';                     
    status: 'playing' | 'closed';        
    current_member_id?: number | null;
    privatecode: string | null; 
    hash: string;                        
};

// используется в React
export type TUserStats = {
    totalGames: number;
    totalWins: number;
    totalMoney: number;
    totalHours: number;
};

// формат как отвечает PHP
export type TRawUserStats = {
    totalplayed: string;
    totalwin: string;
    totalbalance: string;
    totalhours?: string;
};

// Игрок в комнате
export type TPlayer = {
    memberId: number;
    userId: number;
    name: string;
    balance: number;
    bet: number;
    cards: string[];
    status: 'spectator' | 'player' ;
};

// Ответ getInfoRoom
export type TRoomInfoResponse = {
    players: TPlayer[];
    myCards: string[];
    timer: number | null;  // Количество секунд, оставшихся на ход (или null)
    hash: string;
    currentPlayerId: number | null;  // ID игрока, чей сейчас ход
    changed: boolean;
};

// Запись пользователя в рейтинг
export type TUserRating = {
    id: number;
    name: string;
    balance: number;
};

// Ответ getRatingTable
export type TLeaderboardResponse = {
    rating: TUserRating[];
};

export type TGetLeaveRoomResponse = {
    success: boolean;
    roomDeleted: boolean;
};
