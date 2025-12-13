import React, { useContext, useEffect, useRef, useState } from 'react';
import { ServerContext } from '../../../App';
import './AdReward.scss';

import soundOffIcon from '../../../assets/img/icons/sound-off.png';
import soundOnIcon from '../../../assets/img/icons/sound-on.png';


type Props = {
  onClose: () => void;
  onSuccess: (newBalance: number) => void;
  videoUrl: string;
};

const REWARD_AMOUNT = 100;
const MAX_BALANCE_FOR_AD = 1000;

const AdReward: React.FC<Props> = ({ onClose, onSuccess, videoUrl }) => {
  const server = useContext(ServerContext);

  const videoRef = useRef<HTMLVideoElement | null>(null);

  const [balance, setBalance] = useState<number | null>(null);
  const [isEligible, setIsEligible] = useState<boolean>(false);
  const [isVideoEnded, setIsVideoEnded] = useState<boolean>(false);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);

  const [muted, setMuted] = useState<boolean>(true);
  const [volume, setVolume] = useState<number>(0.6);

  useEffect(() => {
    let isMounted = true;

    const loadBalance = async () => {
      setIsLoading(true);
      setError(null);

      const currentBalance = await server.getUserBalance();

      if (!isMounted) {
        return;
      }

      if (typeof currentBalance === 'number') {
        setBalance(currentBalance);

        const eligible = currentBalance < MAX_BALANCE_FOR_AD;
        setIsEligible(eligible);

        if (!eligible) {
          setError('Реклама доступна только при балансе меньше 1000');
        }
      } else {
        setError('Не удалось получить баланс. Попробуйте позже.');
      }

      setIsLoading(false);
    };

    loadBalance();

    return () => {
      isMounted = false;
    };
  }, [server]);

  const handleLoadedMetadata = () => {
    const player = videoRef.current;
    if (!player) return;

    player.muted = muted;
    player.volume = volume;

    player.play().catch(() => {
    });
  };

  const handleEnded = () => {
    setIsVideoEnded(true);
    setError(null);
  };

  const handleToggleMute = () => {
    const player = videoRef.current;
    const nextMuted = !muted;

    setMuted(nextMuted);

    if (player) {
      player.muted = nextMuted;
    }
  };

  const handleVolumeChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    const nextVolume = parseFloat(event.target.value);
    const player = videoRef.current;

    setVolume(nextVolume);

    if (player) {
      player.volume = nextVolume;

      if (nextVolume > 0) {
        player.muted = false;
        setMuted(false);
      } else {
        player.muted = true;
        setMuted(true);
      }
    }
  };

  const handleGetReward = async () => {
    if (!isVideoEnded || !isEligible) {
      return;
    }

    setIsLoading(true);
    setError(null);

    const newBalance = await server.addBalance(REWARD_AMOUNT);

    setIsLoading(false);

    if (typeof newBalance === 'number') {
      onSuccess(newBalance);
      onClose();
    } else {
      setError('Не удалось пополнить баланс. Попробуйте позже.');
    }
  };

  const disableControls = isLoading;

  return (
    <div className="ad-reward">
      <div className="ad-reward-card">
        <h2 className="ad-reward-title">Просмотр рекламы</h2>

        {!isEligible && error && (
            <div className="ad-reward-error">{error}</div>
        )}

        <div className="video-wrap">
          <video
            ref={videoRef}
            className="ad-video"
            src={videoUrl}  
            playsInline
            muted={muted}
            autoPlay
            controls={false}
            onLoadedMetadata={handleLoadedMetadata}
            onEnded={handleEnded}
          />

          <div className="custom-controls">
            <button
              className="btn-sound"
              type="button"
              onClick={handleToggleMute}
              disabled={disableControls}
              aria-label={muted ? 'Включить звук' : 'Выключить звук'}
              title={muted ? 'Включить звук' : 'Выключить звук'}
            >

            <img
              src={muted ? soundOffIcon : soundOnIcon}
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
              onChange={handleVolumeChange}
              disabled={disableControls}
            />
          </div>
        </div>

        {error && <div className="ad-reward-error">{error}</div>}

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
            onClick={handleGetReward}
            disabled={!isEligible || !isVideoEnded || isLoading}
          >
            <span>{isVideoEnded ? 'зачислить' : 'сначала досмотрите'}</span>
            <span className="arrow">&gt;</span>
          </button>
        </div>
      </div>
    </div>
  );
};

export default AdReward;
