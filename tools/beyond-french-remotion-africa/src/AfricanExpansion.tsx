import React from 'react';
import {AbsoluteFill, useCurrentFrame, useVideoConfig, interpolate, spring, Easing, Img, staticFile} from 'remotion';

const languages = [
  {
    lang: 'LINGALA',
    greet: 'Mbote!',
    flag: '🇨🇩',
    colors: ['#0a5c36', '#1e90ff'],
    imageLabel: 'Congo River',
    icon: '🪘',
  },
  {
    lang: 'ARABE MAROCAIN',
    greet: 'Shukran! شكرا',
    flag: '🇲🇦',
    colors: ['#c19a3e', '#0a3d8f'],
    imageLabel: 'Chefchaouen',
    icon: '🫕',
  },
  {
    lang: 'ARABE ÉGYPTIEN',
    greet: 'Shokran! شكرا',
    flag: '🇪🇬',
    colors: ['#1a365d', '#d69e2e'],
    imageLabel: 'Pyramids',
    icon: '☥',
  },
  {
    lang: 'SWAHILI',
    greet: 'Asante!',
    flag: '🇸🇴',
    colors: ['#0e7a7b', '#234e52'],
    imageLabel: 'Kilimanjaro',
    icon: '🪘',
  },
];

export const AfricanExpansion: React.FC<{isFeed?: boolean}> = ({isFeed}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  // Title animation
  const titleSpring = spring({
    frame,
    fps,
    config: {damping: 12, stiffness: 100},
  });

  const titleOpacity = interpolate(frame, [0, 20], [0, 1], {extrapolateRight: 'clamp'});
  const titleY = interpolate(titleSpring, [0, 1], [100, 0]);

  // Background zoom
  const bgScale = interpolate(frame, [0, 360], [1, 1.15]);

  return (
    <AbsoluteFill
      style={{
        backgroundColor: '#050a1e',
        fontFamily: 'Inter, system-ui, sans-serif',
        overflow: 'hidden',
      }}
    >
      {/* Egyptian Night Background */}
      <AbsoluteFill
        style={{
          transform: `scale(${bgScale})`,
          background: `
            radial-gradient(ellipse at 50% 20%, rgba(255,215,100,0.15) 0%, transparent 50%),
            radial-gradient(ellipse at 20% 80%, rgba(10,92,54,0.2) 0%, transparent 40%),
            linear-gradient(180deg, #0a0f2c 0%, #050a1e 40%, #0a1a3a 100%)
          `,
        }}
      >
        {/* Moon */}
        <div
          style={{
            position: 'absolute',
            top: 120,
            left: '50%',
            transform: 'translateX(-50%)',
            width: 220,
            height: 220,
            borderRadius: '50%',
            background: 'radial-gradient(circle at 30% 30%, #fffbe6, #f5e6a3, #d4b76a)',
            boxShadow: '0 0 80px rgba(255,235,150,0.6), 0 0 120px rgba(255,215,100,0.3)',
            opacity: interpolate(frame, [0, 30], [0, 1]),
          }}
        />
        {/* Stars */}
        {Array.from({length: 80}).map((_, i) => (
          <div
            key={i}
            style={{
              position: 'absolute',
              left: `${(i * 37) % 100}%`,
              top: `${(i * 57) % 60}%`,
              width: 2 + (i % 3),
              height: 2 + (i % 3),
              background: 'white',
              borderRadius: '50%',
              opacity: 0.3 + Math.random() * 0.7,
            }}
          />
        ))}
        {/* Pyramids silhouette */}
        <div
          style={{
            position: 'absolute',
            bottom: 650,
            left: '50%',
            transform: 'translateX(-50%)',
            display: 'flex',
            gap: 20,
            opacity: 0.4,
          }}
        >
          <div style={{width: 0, height: 0, borderLeft: '80px solid transparent', borderRight: '80px solid transparent', borderBottom: '120px solid #1a2744'}} />
          <div style={{width: 0, height: 0, borderLeft: '110px solid transparent', borderRight: '110px solid transparent', borderBottom: '160px solid #1e345c', marginTop: -20}} />
          <div style={{width: 0, height: 0, borderLeft: '60px solid transparent', borderRight: '60px solid transparent', borderBottom: '90px solid #15203a'}} />
        </div>
        {/* Lantern string lights */}
        <div
          style={{
            position: 'absolute',
            top: 400,
            left: 0,
            right: 0,
            height: 2,
            background: 'repeating-linear-gradient(90deg, transparent 0 40px, rgba(255,215,100,0.3) 40px 42px)',
          }}
        />
      </AbsoluteFill>

      {/* Bunting flags */}
      <div style={{position: 'absolute', top: 60, left: 0, right: 0, display: 'flex', justifyContent: 'center', gap: 20, zIndex: 10}}>
        {['🇨🇩','🇲🇦','🇪🇬','🇸🇴'].map((flag, i) => {
          const flagSpring = spring({frame: frame - i*5, fps, config: {damping: 8}});
          return (
            <div
              key={i}
              style={{
                fontSize: 48,
                transform: `translateY(${interpolate(flagSpring, [0,1], [-50,0])}px) rotate(${Math.sin(frame/20 + i)*3}deg)`,
                filter: 'drop-shadow(0 4px 8px rgba(0,0,0,0.5))',
              }}
            >
              {flag}
            </div>
          );
        })}
      </div>

      {/* Title */}
      <div
        style={{
          position: 'absolute',
          top: isFeed ? 140 : 180,
          left: 0,
          right: 0,
          textAlign: 'center',
          transform: `translateY(${titleY}px)`,
          opacity: titleOpacity,
        }}
      >
        <div style={{fontSize: 14, letterSpacing: 6, color: '#d4b76a', marginBottom: 16}}>NOUVEAU LANCEMENT</div>
        <div style={{fontSize: isFeed ? 56 : 64, fontWeight: 900, color: 'white', lineHeight: 0.9}}>
          BEYOND FRENCH
        </div>
        <div style={{fontSize: isFeed ? 36 : 42, fontWeight: 700, color: '#ffd700', marginTop: 8}}>
          S'ÉTEND EN AFRIQUE
        </div>
      </div>

      {/* Cards */}
      <div
        style={{
          position: 'absolute',
          top: isFeed ? 380 : 520,
          left: 40,
          right: 40,
          display: 'flex',
          gap: 18,
          justifyContent: 'center',
        }}
      >
        {languages.map((lang, idx) => {
          const delay = 30 + idx * 15;
          const cardSpring = spring({
            frame: frame - delay,
            fps,
            config: {damping: 14, stiffness: 120},
          });
          const cardOpacity = interpolate(frame, [delay, delay+15], [0,1], {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'});
          const y = interpolate(cardSpring, [0,1], [300, 0]);
          const scale = interpolate(cardSpring, [0,1], [0.7,1]);

          return (
            <div
              key={lang.lang}
              style={{
                flex: 1,
                height: isFeed ? 680 : 820,
                background: 'white',
                borderRadius: 24,
                padding: 18,
                transform: `translateY(${y}px) scale(${scale})`,
                opacity: cardOpacity,
                boxShadow: `0 20px 40px rgba(0,0,0,0.4), 0 0 0 1px rgba(255,215,100,0.2), 0 0 30px ${lang.colors[0]}33`,
                display: 'flex',
                flexDirection: 'column',
              }}
            >
              <div
                style={{
                  background: `linear-gradient(135deg, ${lang.colors[0]}, ${lang.colors[1]})`,
                  color: 'white',
                  fontSize: 11,
                  fontWeight: 700,
                  padding: '6px 12px',
                  borderRadius: 20,
                  alignSelf: 'center',
                  letterSpacing: 1,
                }}
              >
                À VENIR
              </div>

              <div style={{marginTop: 16, textAlign: 'center', fontWeight: 900, fontSize: isFeed ? 18 : 20, color: '#0a0f2c', lineHeight: 1.1}}>
                {lang.lang}
              </div>
              <div style={{textAlign: 'center', fontSize: 16, marginTop: 6, color: '#333'}}>
                {lang.greet}
              </div>
              <div style={{textAlign: 'center', fontSize: 42, marginTop: 10}}>{lang.flag}</div>

              {/* Image placeholder */}
              <div
                style={{
                  marginTop: 14,
                  flex: 1,
                  borderRadius: 16,
                  background: `linear-gradient(180deg, ${lang.colors[0]} 0%, ${lang.colors[1]} 100%)`,
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  position: 'relative',
                  overflow: 'hidden',
                }}
              >
                <div style={{fontSize: 48}}>{lang.icon}</div>
                <div style={{position: 'absolute', bottom: 10, left: 0, right: 0, textAlign: 'center', color: 'rgba(255,255,255,0.7)', fontSize: 10}}>
                  {lang.imageLabel}
                </div>
              </div>

              <div style={{marginTop: 14, display: 'flex', flexDirection: 'column', gap: 6}}>
                {['Conversations Réelles', 'Vocabulaire Pratique', 'Aperçus Culturels'].map((b) => (
                  <div key={b} style={{fontSize: 10, display: 'flex', alignItems: 'center', gap: 6, color: '#444'}}>
                    <span style={{width: 12, height: 12, borderRadius: '50%', background: lang.colors[0], display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'white', fontSize: 8}}>✓</span>
                    {b}
                  </div>
                ))}
              </div>
            </div>
          );
        })}
      </div>

      {/* Bottom pill */}
      {frame > 150 && (
        <div
          style={{
            position: 'absolute',
            bottom: isFeed ? 60 : 80,
            left: '50%',
            transform: `translateX(-50%) translateY(${interpolate(spring({frame: frame-150, fps, config: {damping: 10}}), [0,1], [100,0])}px)`,
            background: '#b91c1c',
            color: 'white',
            padding: '14px 32px',
            borderRadius: 40,
            fontWeight: 800,
            fontSize: 18,
            letterSpacing: 1,
            boxShadow: '0 10px 30px rgba(185,28,28,0.5)',
            display: 'flex',
            alignItems: 'center',
            gap: 12,
          }}
        >
          <span>🌙</span> LANCEMENT AUTOMNE 2026
        </div>
      )}

      {/* Confetti burst at frame 90 */}
      {frame > 80 && frame < 150 && (
        <AbsoluteFill style={{pointerEvents: 'none'}}>
          {Array.from({length: 40}).map((_, i) => {
            const x = (i * 27) % 1080;
            const progress = (frame - 80) / 70;
            const y = interpolate(progress, [0,1], [-20, 1920], {easing: Easing.out(Easing.quad)});
            return (
              <div
                key={i}
                style={{
                  position: 'absolute',
                  left: x,
                  top: y,
                  width: 8,
                  height: 14,
                  background: i % 2 === 0 ? '#ffd700' : '#0a5c36',
                  transform: `rotate(${i * 45 + frame * 3}deg)`,
                  borderRadius: 2,
                }}
              />
            );
          })}
        </AbsoluteFill>
      )}
    </AbsoluteFill>
  );
};
