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
  const isAutoScrolling = useRef(true); // Отслеживаем автопрокрутку
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
    console.log('Loaded messages result:', result); // Для отладки
    
    if (result) {
      const storedMessages = store.getMessages(roomId);
      console.log('Stored messages:', storedMessages); // Для отладки
      
      // ИСПРАВЛЕНИЕ: Всегда сортируем и обновляем состояние
      const sortedMessages = [...storedMessages].sort((a, b) => {
        const dateA = new Date(a.created).getTime();
        const dateB = new Date(b.created).getTime();
        return dateA - dateB;
      });
      setMessages(sortedMessages);
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

  // ИСПРАВЛЕНИЕ 2: Умная автопрокрутка - только если пользователь внизу
  const scrollToBottom = () => {
    if (messagesContainerRef.current && isAutoScrolling.current) {
      messagesContainerRef.current.scrollTop = messagesContainerRef.current.scrollHeight;
    }
  };

  // ИСПРАВЛЕНИЕ 2: Отслеживаем, когда пользователь листает вверх
  const handleScroll = () => {
    if (messagesContainerRef.current) {
      const { scrollTop, scrollHeight, clientHeight } = messagesContainerRef.current;
      const isAtBottom = scrollHeight - scrollTop - clientHeight < 50;
      isAutoScrolling.current = isAtBottom;
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

  // ИСПРАВЛЕНИЕ: НЕ очищаем сообщения, а показываем то, что есть в store
  const existingMessages = store.getMessages(roomId);
  if (existingMessages && existingMessages.length > 0) {
    const sortedMessages = [...existingMessages].sort((a, b) => {
      const dateA = new Date(a.created).getTime();
      const dateB = new Date(b.created).getTime();
      return dateA - dateB;
    });
    setMessages(sortedMessages);
  }

  setError(null);

  // Загружаем начальные сообщения (обновляем с сервера)
  loadMessages();

  // Обработчик новых сообщений
  const handleNewMessages = (newHash: string) => {
    const storedMessages = store.getMessages(roomId);
    // Сортируем при каждом обновлении
    const sortedMessages = [...storedMessages].sort((a, b) => {
      const dateA = new Date(a.created).getTime();
      const dateB = new Date(b.created).getTime();
      return dateA - dateB;
    });
    setMessages(sortedMessages);
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

  const handleKeyPress = (e: React.KeyboardEvent<HTMLInputElement>) => {
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

  // ИСПРАВЛЕНИЕ 3: Скрываем через CSS вместо размонтирования
  return (
    <div 
      className={`chat-popup-overlay ${!isOpen ? 'chat-hidden' : ''}`}
      onClick={isOpen ? onClose : undefined}
      style={{ pointerEvents: isOpen ? 'auto' : 'none' }}
    >
      <div className="chat-popup-content" onClick={(e) => e.stopPropagation()}>
        <div className="chat-popup-header">
          <span className="chat-title">Чат комнаты {roomId ? `#${roomId}` : ''}</span>
          <button className="chat-popup-close" onClick={onClose} aria-label="Закрыть чат">
            ×
          </button>
        </div>

        {/* ИСПРАВЛЕНИЕ 2: Добавляем обработчик прокрутки */}
        <div 
          className="chat-popup-messages" 
          ref={messagesContainerRef}
          onScroll={handleScroll}
        >
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
                    <span className="message-time">{formatMessageTime(message.created)}</span>
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
            placeholder={!roomId ? "Выберите комнату" : "Введите сообщение..."} 
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
