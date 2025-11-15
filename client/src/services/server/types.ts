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

export type TJoinPrivateRoomResponse = {
    room: {
        id: number;
        type: string;
        status: string;
        private_code: string;
        players: Array<{
            id: number;
            name: string;
            balance: number;
        }>;
    }
};

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
