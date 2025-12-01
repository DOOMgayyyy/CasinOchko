import './Login.scss';
import { ServerContext } from '../../App';
import React, { useState, useRef, useContext } from 'react';
import { IBasePage, PAGES } from '../PageManager';
import SnowEffect from '../Lobby/SnowEffect';


const Login: React.FC<IBasePage> = (props) => {
  const { setPage } = props;
  const [showPwd, setShowPwd] = useState(false);
  const server = useContext(ServerContext);
  const loginRef = useRef<HTMLInputElement>(null);
  const passwordRef = useRef<HTMLInputElement>(null);

    const loginClickHandler = async () => {
        if (loginRef.current && passwordRef.current) {
          const email = loginRef.current.value;
          const password = passwordRef.current.value;
          if (email && password && await server.login(email, password)) {
              setPage(PAGES.LOBBY);
          }
        }
    }

    //=================DEV-ЗАГЛУШКА======================
    // const loginClickHandler = async () => {
    //   setPage(PAGES.LOBBY);
    // };
    //===================================================


    const setRegisterPage = async () => {
      setPage(PAGES.REGISTER);
    }   
 
  return (
    <div className="main-login">
      <SnowEffect />
      <main className="sign-wrap">
          {/* ЛОГОТИП */}
        <div className="brand-logo" aria-label="CASINOCHKO">
          <span className="brand-white">CASIN</span>
          <span className="brand-yellow">OCHKO</span>
        </div>

        <h1 className="sign-title">Добро Пожаловать!</h1>

        <section className="sign-card" role="form" aria-label="Форма входа">
          <div className="field">
            <label className="label" htmlFor="email">Email:</label>
            <input
              ref={loginRef}
              className="input"
              id="email"
              type="email"
              placeholder="you@example.com"
              autoComplete="username"
            />
          </div>

          <div className="field">
            <label className="label" htmlFor="password">Пароль:</label>
            <div className="password-wrap">
              <input
                ref={passwordRef}
                className="input"
                id="password"
                type={showPwd ? 'text' : 'password'}
                placeholder="••••••••"
                autoComplete="current-password"
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

          <div className="actions">
            <button className="btn-link" type="button">
              <span className="arrow">&gt;</span>
              <span onClick = {loginClickHandler}>войти</span>
            </button>
          </div>
        </section>

        <div className="foot">
          Впервые здесь?
          <button className="foot-link" onClick={setRegisterPage}>
            <span className="arrow">&gt;</span>
            <span>зарегистрироваться</span>
          </button>
        </div>
      </main>
    </div>
  );
};

export default Login;
