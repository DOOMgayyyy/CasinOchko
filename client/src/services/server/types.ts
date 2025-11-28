export type TError = {
    code: number;
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

export type TPrivateRoomResponse = {
    id: number;                          
    type: 'private';                     
    status: 'playing' | 'closed';        
    current_member_id?: number | null;   // ID игрока, чей сейчас ход
    private_code: string;                
    hash: string;                        
};

export type TJoinPrivateRoomResponse = TPrivateRoomResponse;

export type TQuickStartResponse = {
    id: number;                          
    type: 'open';                       
    status: 'playing' | 'closed';       
    current_member_id?: number | null;   // ID игрока, чей сейчас ход
    private_code: null;                  
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
    total_played: string;
    total_win: string;
    total_balance: string;
    total_hours?: string;
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

// Таймер хода
export type TTimer = {
    currentPlayerId: number;
    timeLeft: number | null;
    totalTime: number | null;
};

// Ответ getInfoRoom
export type TRoomInfoResponse = {
    players: TPlayer[];
    myCards: string[];
    timer: TTimer | null;
    hash: string;
    currentPlayerId: number | null;
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
