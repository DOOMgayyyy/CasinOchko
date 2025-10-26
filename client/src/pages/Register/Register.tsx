import './Register.scss';
import React, { useState, useRef, useContext } from 'react'; 
import { ServerContext } from '../../App';
import { IBasePage, PAGES } from '../PageManager';


const Register: React.FC<IBasePage> = (props) => {
  const { setPage } = props;
  const [showPwd, setShowPwd] = useState(false);
  const [showConfirm, setShowConfirm] = useState(false);
  
  const server = useContext(ServerContext);
  const emailRef = useRef<HTMLInputElement>(null);
  const passwordRef = useRef<HTMLInputElement>(null);
  const passwordConfirmRef = useRef<HTMLInputElement>(null);
  const nameRef = useRef<HTMLInputElement>(null);
  const registerClickHandler = async () => {
    if (emailRef.current && passwordRef.current && passwordConfirmRef.current && nameRef.current) {
      const email = emailRef.current.value;
      const password = passwordRef.current.value;
      const passwordConfirm = passwordConfirmRef.current.value;
      const name = nameRef.current.value;

      if (password !== passwordConfirm) {
        console.error("Пароли не совпадают");
        return;
      }
      
      // Вызываем метод регистрации
      const success = await server.registration(email, password, name);
      if (success) {
        console.log("Регистрация прошла успешно");
        setPage(PAGES.LOBBY); 
      } else {
        console.error("Ошибка регистрации");
        // Здесь можно показать ошибку от сервера
      }
    }
  };

  const setLoginPage = () => setPage(PAGES.LOGIN);

  return (
    <div className="main-register">
      <main className="sign-wrap">
        {/* ЛОГОТИП */}
        <div className="brand-logo" aria-label="CASINOCHKO">
          <span className="brand-white">CASIN</span>
          <span className="brand-yellow">OCHKO</span>
        </div>

        <h1 className="sign-title">Регистрация</h1>

        <section className="sign-card" role="form" aria-label="Форма регистрации">
          <div className="field">
            <label className="label" htmlFor="email">Email:</label>
            <input
              ref={emailRef} // <--- ref
              className="input"
              id="email"
              type="email"
              placeholder="your@example.com"
              autoComplete="username"
            />
          </div>

        <div className="field">
          <label className="label" htmlFor="password">Пароль:</label>
          <div className="password-wrap">
            <input
              ref={passwordRef} // <--- ref
              className="input"
              id="password"
              type={showPwd ? 'text' : 'password'}
              placeholder="••••••••"
              autoComplete="new-password"
            />
              <button
                type="button"
                className="toggle-visibility"
                onClick={() => setShowPwd(v => !v)}
                aria-label={showPwd ? 'Скрыть пароль' : 'Показать пароль'}
                aria-pressed={showPwd}
                title={showPwd ? 'Скрыть пароль' : 'Показать пароль'}
              >
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"
                     aria-hidden="true">
                  <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
            </div>
          </div>

          <div className="field">
            <label className="label" htmlFor="passwordConfirm">Повторите пароль:</label>
            <div className="password-wrap">
              <input
                ref={passwordConfirmRef} // <--- ref
                className="input"
                id="passwordConfirm"
                type={showConfirm ? 'text' : 'password'}
                placeholder="••••••••"
                autoComplete="new-password"
              />
              <button
                type="button"
                className="toggle-visibility"
                onClick={() => setShowConfirm(v => !v)}
                aria-label={showConfirm ? 'Скрыть пароль' : 'Показать пароль'}
                aria-pressed={showConfirm}
                title={showConfirm ? 'Скрыть пароль' : 'Показать пароль'}
              >
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"
                     aria-hidden="true">
                  <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
            </div>
          </div>

        <div className="field">
          <label className="label" htmlFor="username">Ваше имя:</label>
          <input 
            ref={nameRef} // <--- ref
            className="input" 
            id="username" 
            type="text" 
            autoComplete="nickname" 
            placeholder="username" 
          />
        </div>

        <div className="actions actions-split">
          <a className="btn-link" href="#Login" onClick={(e) => { e.preventDefault(); setLoginPage(); }}>
            <span className="arrow">&lt;</span>
            <span>авторизация</span>
          </a>

          <button className="btn-link" type="button" onClick={registerClickHandler}>
            <span className="arrow">&gt;</span>
            <span>создать аккаунт</span>
          </button>
          </div>
        </section>
      </main>
    </div>
  );
};

export default Register;
