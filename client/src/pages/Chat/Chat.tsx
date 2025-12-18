import React, { useContext, useEffect, useState, useRef, useCallback } from 'react';
import { ServerContext, StoreContext } from '../../App';
import { TMessages } from '../../services/server/types';
import './Chat.scss';

interface ChatPopupProps {
    isOpen: boolean;
    onClose: () => void;
    roomId?: number | null;
}

const ChatPopup: React.FC<ChatPopupProps> = ({ isOpen, onClose, roomId }) => {
    const server = useContext(ServerContext);
    const store = useContext(StoreContext);
    const [messages, setMessages] = useState<TMessages>([]);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const messageRef = useRef<HTMLInputElement>(null);
    const messagesEndRef = useRef<HTMLDivElement>(null);
    const messagesContainerRef = useRef<HTMLDivElement>(null);
    const user = store.getUser();

    // Функция для загрузки сообщений
    const loadMessages = useCallback(async () => {
        if (!user || !roomId) {
            setError("Невозможно загрузить чат: нет пользователя или комнаты");
            return;
        }
        
        setIsLoading(true);
        setError(null);
        try {
            const result = await server.getMessages(roomId);
            if (result) {
                const storedMessages = store.getMessages(roomId);
                setMessages(storedMessages);
            }
        } catch (error) {
            console.error('Error loading messages:', error);
            setError("Ошибка при загрузке сообщений");
        } finally {
            setIsLoading(false);
        }
    }, [user, server, store, roomId]);

    // Функция для отправки сообщения
    const sendMessage = useCallback(async (message: string) => {
        if (!user || !message.trim() || !roomId) {
            setError("Невозможно отправить сообщение");
            return;
        }
        
        try {
            await server.sendMessage(message, roomId);
            await loadMessages();
        } catch (error) {
            console.error('Error sending message:', error);
            setError("Ошибка при отправке сообщения");
        }
    }, [user, server, roomId, loadMessages]);

    // Автопрокрутка к новым сообщениям
    const scrollToBottom = () => {
        if (messagesContainerRef.current) {
            messagesContainerRef.current.scrollTop = messagesContainerRef.current.scrollHeight;
        }
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages]);

    // Инициализация чата при открытии
    useEffect(() => {
        if (!isOpen || !user || !roomId) {
            if (isOpen && (!user || !roomId)) {
                setError(user ? "Нет доступа к чату комнаты" : "Войдите в систему");
            }
            return;
        }

        // Очищаем предыдущие сообщения
        store.clearMessages(roomId);
        setMessages([]);
        setError(null);
        
        // Загружаем начальные сообщения
        loadMessages();

        // Обработчик новых сообщений
        const handleNewMessages = (newHash: string) => {
            const storedMessages = store.getMessages(roomId);
            setMessages(storedMessages);
        };

        // Запускаем получение сообщений
        server.startChatMessages(handleNewMessages, roomId);

        // Очистка при закрытии
        return () => {
            server.stopChatMessages();
        };
    }, [isOpen, user, roomId, server, store, loadMessages]);

    // Обработчик отправки сообщения
    const handleSendMessage = async () => {
        if (messageRef.current && messageRef.current.value.trim()) {
            const message = messageRef.current.value.trim();
            messageRef.current.value = '';
            await sendMessage(message);
        }
    };

    const handleKeyPress = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSendMessage();
        }
    };

    const formatMessageTime = (timestamp: string) => {
        try {
            const date = new Date(timestamp);
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        } catch {
            return timestamp;
        }
    };

    if (!isOpen) return null;

    return (
        <div className="chat-popup-overlay" onClick={onClose}>
            <div className="chat-popup-content" onClick={(e) => e.stopPropagation()}>
                <div className="chat-popup-header">
                    <span className="chat-title">
                        Чат комнаты {roomId ? `#${roomId}` : ''}
                    </span>
                    <button className="chat-popup-close" onClick={onClose} aria-label="Закрыть чат">
                        ×
                    </button>
                </div>
                
                <div className="chat-popup-messages" ref={messagesContainerRef}>
                    {error ? (
                        <div className="chat-error">{error}</div>
                    ) : isLoading ? (
                        <div className="chat-loading">Загрузка сообщений...</div>
                    ) : messages.length === 0 ? (
                        <div className="chat-empty">Сообщений пока нет. Будьте первым!</div>
                    ) : (
                        messages.map((message, index) => {
                            const isMyMessage = user && message.author === user.name;
                            return (
                                <div 
                                    key={`${message.author}-${message.created}-${index}`} 
                                    className={`chat-message ${isMyMessage ? 'my-message' : ''}`}
                                >
                                    <div className="message-header">
                                        <span className="message-author">{message.author}</span>
                                        <span className="message-time">
                                            {formatMessageTime(message.created)}
                                        </span>
                                    </div>
                                    <div className="message-text">{message.message}</div>
                                </div>
                            );
                        })
                    )}
                    <div ref={messagesEndRef} />
                </div>
                
                <div className="chat-popup-input-container">
                    <input 
                        ref={messageRef} 
                        placeholder={!roomId ? 'Чат недоступен' : 'Введите сообщение...'} 
                        className="chat-popup-input"
                        onKeyPress={handleKeyPress}
                        disabled={!user || isLoading || !roomId}
                        maxLength={500}
                    />
                    <button 
                        className="send-chat-btn" 
                        onClick={handleSendMessage}
                        disabled={!user || isLoading || !roomId}
                    >
                        {isLoading ? '...' : 'Отправить'}
                    </button>
                </div>
            </div>
        </div>
    );
};

export default ChatPopup;