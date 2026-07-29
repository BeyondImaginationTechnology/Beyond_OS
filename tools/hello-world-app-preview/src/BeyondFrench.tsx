import React from 'react';
import {
  AbsoluteFill,
  Easing,
  Img,
  interpolate,
  Sequence,
  staticFile,
  useCurrentFrame,
} from 'remotion';

const C = {
  white: '#F8FAFF',
  muted: '#B6C0D8',
  blue: '#1677FF',
  red: '#FF3B4E',
  gold: '#FFD84D',
  green: '#38D978',
};

const enter = (frame: number, start: number, distance = 70) => ({
  opacity: interpolate(frame, [start, start + 14], [0, 1], {
    extrapolateLeft: 'clamp' as const,
    extrapolateRight: 'clamp' as const,
  }),
  translate: `0 ${interpolate(frame, [start, start + 18], [distance, 0], {
    extrapolateLeft: 'clamp',
    extrapolateRight: 'clamp',
    easing: Easing.bezier(0.16, 1, 0.3, 1),
  })}px`,
});

const Scene: React.FC<{
  from: number;
  duration: number;
  children: React.ReactNode;
}> = ({from, duration, children}) => {
  const frame = useCurrentFrame();
  const local = frame;
  return (
    <div
      style={{
        opacity: interpolate(
          local,
          [0, 12, duration - 12, duration],
          [0, 1, 1, 0],
          {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'},
        ),
        width: '100%',
        height: '100%',
      }}
    >
      {children}
    </div>
  );
};

const Background: React.FC = () => {
  const frame = useCurrentFrame();
  return (
    <AbsoluteFill
      style={{
        overflow: 'hidden',
        background:
          'radial-gradient(circle at 18% 20%, rgba(19,79,183,.36), transparent 34%), radial-gradient(circle at 84% 70%, rgba(182,22,50,.25), transparent 36%), #030712',
      }}
    >
      {[C.blue, C.red, C.gold, C.green].map((color, index) => (
        <div
          key={color}
          style={{
            position: 'absolute',
            width: 740,
            height: 8,
            borderRadius: 8,
            background: color,
            boxShadow: `0 0 34px ${color}`,
            opacity: 0.38,
            left: index % 2 === 0 ? -250 : 550,
            top: 300 + index * 380,
            rotate: `${-22 + index * 11}deg`,
            translate: `${interpolate(frame, [0, 299], [-80, 100])}px 0`,
          }}
        />
      ))}
      <div
        style={{
          position: 'absolute',
          inset: 0,
          background:
            'linear-gradient(180deg, rgba(3,7,18,.12), rgba(3,7,18,.66))',
        }}
      />
    </AbsoluteFill>
  );
};

const Brand: React.FC = () => (
  <div
    style={{
      display: 'flex',
      alignItems: 'center',
      gap: 18,
      color: C.white,
      fontSize: 30,
      fontWeight: 800,
      letterSpacing: 3,
      textTransform: 'uppercase',
    }}
  >
    <Img
      src={staticFile('beyond-french/app-icon.png')}
      style={{width: 56, height: 56, borderRadius: 15}}
    />
    Beyond French
  </div>
);

const Headline: React.FC<{
  kicker: string;
  children: React.ReactNode;
  start?: number;
}> = ({kicker, children, start = 0}) => {
  const frame = useCurrentFrame();
  return (
    <div
      style={{
        ...enter(frame, start),
        display: 'flex',
        flexDirection: 'column',
        gap: 28,
        width: 900,
      }}
    >
      <div
        style={{
          color: C.blue,
          fontSize: 34,
          fontWeight: 850,
          letterSpacing: 6,
          textTransform: 'uppercase',
        }}
      >
        {kicker}
      </div>
      <div
        style={{
          whiteSpace: 'pre-line',
          fontSize: 116,
          fontWeight: 800,
          lineHeight: 0.98,
          letterSpacing: -7,
        }}
      >
        {children}
      </div>
    </div>
  );
};

const LanguagePill: React.FC<{
  color: string;
  flag: string;
  label: string;
  phrase: string;
  index: number;
}> = ({color, flag, label, phrase, index}) => {
  const frame = useCurrentFrame();
  return (
    <div
      style={{
        ...enter(frame, 18 + index * 7, 40),
        display: 'flex',
        alignItems: 'center',
        gap: 24,
        width: 850,
        padding: '25px 30px',
        borderRadius: 28,
        background: 'rgba(10,17,35,.86)',
        border: `2px solid ${color}66`,
        boxShadow: `0 12px 44px ${color}19`,
      }}
    >
      <div style={{fontSize: 48}}>{flag}</div>
      <div style={{flex: 1}}>
        <div
          style={{
            color,
            fontSize: 27,
            fontWeight: 800,
            textTransform: 'uppercase',
            letterSpacing: 2,
          }}
        >
          {label}
        </div>
        <div style={{fontSize: 48, fontWeight: 750}}>{phrase}</div>
      </div>
      <div style={{fontSize: 34, color: C.muted}}>●)))</div>
    </div>
  );
};

export const BeyondFrenchVideo: React.FC = () => {
  const frame = useCurrentFrame();
  return (
    <AbsoluteFill
      style={{
        color: C.white,
        fontFamily: 'Arial, Helvetica, sans-serif',
      }}
    >
      <Background />
      <AbsoluteFill style={{padding: '92px 84px 110px'}}>
        <div style={enter(frame, 0, 20)}>
          <Brand />
        </div>

        <Sequence from={0} durationInFrames={92} layout="none">
          <Scene from={0} duration={92}>
            <div
              style={{
                height: '100%',
                display: 'flex',
                flexDirection: 'column',
                justifyContent: 'center',
                gap: 70,
              }}
            >
              <Headline kicker="Learn beyond translation">
                {'One phrase.\nFour languages.'}
              </Headline>
              <div
                style={{
                  ...enter(frame, 18),
                  fontSize: 44,
                  color: C.muted,
                  lineHeight: 1.35,
                }}
              >
                French, Haitian Creole, Spanish,
                <br />
                and Jamaican Patois—together.
              </div>
            </div>
          </Scene>
        </Sequence>

        <Sequence from={82} durationInFrames={126} layout="none">
          <Scene from={82} duration={126}>
            <div
              style={{
                height: '100%',
                display: 'flex',
                flexDirection: 'column',
                justifyContent: 'center',
                alignItems: 'center',
                gap: 42,
              }}
            >
              <div
                style={{
                  color: C.white,
                  fontSize: 86,
                  fontWeight: 800,
                  letterSpacing: -4,
                  marginBottom: 14,
                }}
              >
                Say “hello” your way.
              </div>
              <LanguagePill
                index={0}
                color={C.blue}
                flag="🇫🇷"
                label="French"
                phrase="Bonjour"
              />
              <LanguagePill
                index={1}
                color={C.red}
                flag="🇭🇹"
                label="Haitian Creole"
                phrase="Bonjou"
              />
              <LanguagePill
                index={2}
                color={C.gold}
                flag="🇪🇸"
                label="Spanish"
                phrase="Hola"
              />
              <LanguagePill
                index={3}
                color={C.green}
                flag="🇯🇲"
                label="Jamaican Patois"
                phrase="Wah Gwaan"
              />
            </div>
          </Scene>
        </Sequence>

        <Sequence from={198} durationInFrames={102} layout="none">
          <Scene from={198} duration={102}>
            <div
              style={{
                height: '100%',
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                justifyContent: 'center',
                gap: 48,
                textAlign: 'center',
              }}
            >
              <Img
                src={staticFile('beyond-french/app-icon.png')}
                style={{
                  ...enter(frame, 8, 45),
                  width: 260,
                  height: 260,
                  borderRadius: 62,
                  boxShadow: '0 40px 90px rgba(22,119,255,.35)',
                }}
              />
              <div
                style={{
                  ...enter(frame, 16, 45),
                  fontSize: 100,
                  lineHeight: 1,
                  fontWeight: 850,
                  letterSpacing: -6,
                }}
              >
                A little every day.
                <br />
                Beyond fluent.
              </div>
              <div
                style={{
                  ...enter(frame, 26, 35),
                  padding: '26px 48px',
                  borderRadius: 28,
                  background: C.white,
                  color: '#030712',
                  fontSize: 36,
                  fontWeight: 800,
                }}
              >
                Download on the App Store
              </div>
            </div>
          </Scene>
        </Sequence>
      </AbsoluteFill>
    </AbsoluteFill>
  );
};
