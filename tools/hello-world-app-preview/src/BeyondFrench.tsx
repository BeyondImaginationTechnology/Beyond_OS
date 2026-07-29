import React from 'react';
import {Audio} from '@remotion/media';
import {
  AbsoluteFill,
  Easing,
  Img,
  interpolate,
  Sequence,
  staticFile,
  useCurrentFrame,
  useVideoConfig,
} from 'remotion';

const C = {
  white: '#F8FAFF',
  muted: '#B6C0D8',
  blue: '#1677FF',
  red: '#FF3B4E',
  gold: '#FFD84D',
  green: '#38D978',
};

export type BeyondFrenchVideoProps = {
  english: string;
  french: string;
  kreyol: string;
  spanish: string;
  patois: string;
  category: string;
  audioFile?: string;
  brandIcon?: string;
};

const mediaSource = (source: string) =>
  /^(blob:|data:|https?:\/\/|\/)/i.test(source) ? source : staticFile(source);

export const defaultBeyondFrenchVideoProps: BeyondFrenchVideoProps = {
  english: 'Keep going.',
  french: 'Continue.',
  kreyol: 'Kontinye.',
  spanish: 'Sigue adelante.',
  patois: 'Keep on gwaan.',
  category: 'Encouragement',
  audioFile: '',
  brandIcon: 'beyond-french/app-icon.png',
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

const Brand: React.FC<{icon: string}> = ({icon}) => (
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
      src={mediaSource(icon)}
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

export const BeyondFrenchVideo: React.FC<BeyondFrenchVideoProps> = ({
  english,
  french,
  kreyol,
  spanish,
  patois,
  category,
  audioFile,
  brandIcon = defaultBeyondFrenchVideoProps.brandIcon,
}) => {
  const frame = useCurrentFrame();
  const {durationInFrames} = useVideoConfig();
  const closingDuration = Math.max(102, durationInFrames - 198);
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
          <Brand icon={brandIcon} />
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
              <Headline kicker={`Today's ${category} lesson`}>
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
                One phrase. Four languages.
              </div>
              <LanguagePill
                index={0}
                color={C.blue}
                flag="🇫🇷"
                label="French"
                phrase={french}
              />
              <LanguagePill
                index={1}
                color={C.red}
                flag="🇭🇹"
                label="Haitian Creole"
                phrase={kreyol}
              />
              <LanguagePill
                index={2}
                color={C.gold}
                flag="🇪🇸"
                label="Spanish"
                phrase={spanish}
              />
              <LanguagePill
                index={3}
                color={C.green}
                flag="🇯🇲"
                label="Jamaican Patois"
                phrase={patois}
              />
            </div>
          </Scene>
        </Sequence>

        <Sequence from={198} durationInFrames={closingDuration} layout="none">
          <Scene from={198} duration={closingDuration}>
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
                src={mediaSource(brandIcon)}
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
                Download on Beyond Store
              </div>
              <div
                style={{
                  ...enter(frame, 32, 28),
                  display: 'flex',
                  flexDirection: 'column',
                  alignItems: 'center',
                  gap: 8,
                  color: C.muted,
                }}
              >
                <div
                  style={{
                    fontSize: 38,
                    fontWeight: 800,
                    letterSpacing: 1,
                  }}
                >
                  Google Play + App Store
                </div>
                <div
                  style={{
                    color: C.gold,
                    fontSize: 30,
                    fontWeight: 850,
                    letterSpacing: 3,
                    textTransform: 'uppercase',
                  }}
                >
                  Coming Fall 2026
                </div>
              </div>
            </div>
          </Scene>
        </Sequence>
      </AbsoluteFill>
      {audioFile ? <Audio src={mediaSource(audioFile)} volume={0.96} /> : null}
    </AbsoluteFill>
  );
};
