import React, { useContext, useEffect, useRef, useState } from 'react';
import { ServerContext } from '../../../App';
import './AdReward.scss';

type Props = {
  onClose: () => void;
  onSuccess: (newBalance: number) => void;
  videoUrl: string;
};

const AdReward: React.FC<Props> = ({ onClose, onSuccess, videoUrl }) => {
  const server = useContext(ServerContext);

  const videoRef = useRef<HTMLVideoElement | null>(null);
  const lastTimeRef = useRef(0);
  const durationRef = useRef(0);
  const [isEligible, setIsEligible] = useState<boolean | null>(null);
  const [balance, setBalance] = useState<number | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [muted, setMuted] = useState(true);
  const [volume, setVolume] = useState(0.6);
  const [watchedToEnd, setWatchedToEnd] = useState(false);

  const ERROR_CODES = {
    NOT_ELIGIBLE: 2001,
    VIDEO_NOT_WATCHED: 2002,
    TOPUP_FAIL: 2003,
    LOAD_BALANCE_FAIL: 2004,
  };

  const fetchUserBalance = async (): Promise<number | null> => {
    try {
      const balance = await server.getUserBalance();
      return typeof balance === 'number' ? balance : null;
    } catch (err) {
      console.error('Ошибка при получении баланса:', err);
      return null;
    }
  };

  const topupByAd = async (): Promise<{ ok: boolean; newBalance?: number }> => {
    try {
      const resp = await server.addBalance(100);
      return resp || { ok: false };
    } catch (err) {
      console.error('Ошибка при пополнении через рекламу:', err);
      return { ok: false };
    }
  };

  useEffect(() => {
    let isMounted = true;
    (async () => {
      setIsLoading(true);
      const b = await fetchUserBalance();
      if (!isMounted) return;

      if (b === null) {
        server.showErrorCb?.({
          code: ERROR_CODES.LOAD_BALANCE_FAIL,
          text: 'Не удалось получить баланс. Попробуйте позже.',
        });
        setIsEligible(false);
      } else {
        setBalance(b);
        setIsEligible(b < 1000);
        if (b >= 1000) {
          server.showErrorCb?.({
            code: ERROR_CODES.NOT_ELIGIBLE,
            text: 'Ваш баланс ≥ 1000. Просмотр рекламы недоступен.',
          });
        }
      }
      setIsLoading(false);
    })();
    return () => {
      isMounted = false;
    };
  }, []);

  const onLoadedMetadata = () => {
    const v = videoRef.current;
    if (!v) return;
    durationRef.current = v.duration || 0;
    v.currentTime = 0;
    lastTimeRef.current = 0;
    v.muted = muted;
    v.volume = volume;
  };

  const onTimeUpdate = () => {
    const v = videoRef.current;
    if (!v) return;
    const now = v.currentTime;

    if (now - lastTimeRef.current > 1.25) {
      v.currentTime = lastTimeRef.current;
      return;
    }
    lastTimeRef.current = now;
  };

  const onSeeking = () => {
    const v = videoRef.current;
    if (!v) return;
    v.currentTime = lastTimeRef.current;
  };

  const onPause = () => {
    const v = videoRef.current;
    if (!v) return;
    if (!v.ended) {
      v.play().catch(() => {});
    }
  };

  const onEnded = async () => {
    const v = videoRef.current;
    if (!v) return;

    const duration =
      (typeof durationRef.current === 'number' && durationRef.current) ||
      v.duration ||
      0;

    const watched = v.currentTime >= Math.max(0, duration - 0.4);

    if (!watched) {
      server.showErrorCb?.({
        code: ERROR_CODES.VIDEO_NOT_WATCHED,
        text: 'Видео нужно досмотреть до конца.',
      });
      return;
    }

    setWatchedToEnd(true);

    if (!isEligible) {
      server.showErrorCb?.({
        code: ERROR_CODES.NOT_ELIGIBLE,
        text: 'Баланс не подходит для пополнения через рекламу.',
      });
      return;
    }

    setIsLoading(true);
    const resp = await topupByAd();
    setIsLoading(false);

    if (resp.ok) {
      const newB =
        typeof resp.newBalance === 'number' ? resp.newBalance : balance ?? 0;
      onSuccess(newB);
      onClose();
    } else {
      server.showErrorCb?.({
        code: ERROR_CODES.TOPUP_FAIL,
        text: 'Не удалось пополнить баланс. Попробуйте позже.',
      });
    }
  };

  const toggleMute = () => {
    const v = videoRef.current;
    const next = !muted;
    setMuted(next);
    if (v) v.muted = next;
  };

  const changeVolume = (e: React.ChangeEvent<HTMLInputElement>) => {
    const next = parseFloat(e.target.value);
    setVolume(next);
    const v = videoRef.current;
    if (v) {
      v.volume = next;
      if (next > 0) {
        v.muted = false;
        setMuted(false);
      } else {
        v.muted = true;
        setMuted(true);
      }
    }
  };

  const disableAll = isLoading || isEligible === null;

  return (
    <div className="ad-reward">
      <div className="ad-reward-card">
        <h2 className="ad-reward-title">Просмотр рекламы</h2>

        {isEligible === false && (
          <div className="ad-reward-note disabled">
            Баланс: <b>${balance ?? '—'}</b>
            <br />
            <span>Реклама доступна только при балансе меньше 1000</span>
          </div>
        )}

        <div className="video-wrap">
          <video
            ref={videoRef}
            className="ad-video"
            src={videoUrl}
            playsInline
            muted={muted}
            autoPlay
            onEnded={onEnded}
          />
          <div className="custom-controls">
            <button
              className="btn-sound"
              type="button"
              onClick={toggleMute}
              disabled={disableAll}
              aria-label={muted ? 'Включить звук' : 'Выключить звук'}
              title={muted ? 'Включить звук' : 'Выключить звук'}
            >
              <img
                src={
                  muted
                    ? require('../../../assets/img/icons/sound-off.png')
                    : require('../../../assets/img/icons/sound-on.png')
                }
                alt={muted ? 'Звук выключен' : 'Звук включен'}
                className="sound-icon"
              />
            </button>

            <input
              className="volume"
              type="range"
              min={0}
              max={1}
              step={0.01}
              value={volume}
              onChange={changeVolume}
              disabled={disableAll}
            />
          </div>
        </div>

        <div className="actions">
          <button
            type="button"
            className="btn-link"
            onClick={onClose}
            disabled={isLoading}
          >
            <span className="arrow">&lt;</span>
            <span>закрыть</span>
          </button>

          <button
            type="button"
            className="btn-submit"
            disabled={
              isLoading ||
              isEligible === false ||
              isEligible === null ||
              !watchedToEnd
            }
            onClick={onEnded}
          >
            <span>{watchedToEnd ? 'зачислить' : 'сначала досмотрите'}</span>
            <span className="arrow">&gt;</span>
          </button>
        </div>
      </div>
    </div>
  );
};

export default AdReward;