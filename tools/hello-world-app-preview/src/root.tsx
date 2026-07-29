import React from 'react';
import {
  AbsoluteFill,
  Composition,
  Easing,
  interpolate,
  Sequence,
  useCurrentFrame,
} from 'remotion';
import {
  BeyondFrenchVideo,
  defaultBeyondFrenchVideoProps,
} from './BeyondFrench';

const colors = {
  ink: '#F8FAFF',
  muted: '#B8C0DE',
  cyan: '#5EEBFF',
  violet: '#9B7BFF',
  dark: '#070A18',
};

const fade = (frame: number, start: number, end: number) =>
  interpolate(frame, [start, start + 12, end - 12, end], [0, 1, 1, 0], {
    extrapolateLeft: 'clamp',
    extrapolateRight: 'clamp',
    easing: Easing.bezier(0.16, 1, 0.3, 1),
  });

const Background: React.FC = () => {
  const frame = useCurrentFrame();
  return (
    <AbsoluteFill
      style={{
        background:
          'radial-gradient(circle at 20% 18%, #27214F 0, transparent 36%), radial-gradient(circle at 80% 68%, #0E5261 0, transparent 38%), #070A18',
        overflow: 'hidden',
      }}
    >
      <div
        style={{
          position: 'absolute',
          width: 720,
          height: 720,
          borderRadius: '50%',
          border: '2px solid rgba(155,123,255,.18)',
          left: -330,
          top: 350,
          scale: interpolate(frame, [0, 239], [0.8, 1.18]),
          rotate: `${interpolate(frame, [0, 239], [-10, 18])}deg`,
        }}
      />
      <div
        style={{
          position: 'absolute',
          width: 500,
          height: 500,
          borderRadius: '50%',
          filter: 'blur(90px)',
          background: 'rgba(94,235,255,.12)',
          right: -200,
          bottom: 120,
          translate: `${interpolate(frame, [0, 239], [0, -80])}px 0`,
        }}
      />
    </AbsoluteFill>
  );
};

const Wordmark: React.FC = () => (
  <div
    style={{
      display: 'flex',
      alignItems: 'center',
      gap: 18,
      fontFamily: 'Arial, Helvetica, sans-serif',
      fontSize: 32,
      fontWeight: 700,
      letterSpacing: 4,
      color: colors.ink,
      textTransform: 'uppercase',
    }}
  >
    <div
      style={{
        width: 34,
        height: 34,
        borderRadius: 10,
        background: `linear-gradient(135deg, ${colors.violet}, ${colors.cyan})`,
        boxShadow: '0 0 36px rgba(94,235,255,.4)',
      }}
    />
    Hello
  </div>
);

const Phone: React.FC = () => {
  const frame = useCurrentFrame();
  return (
    <div
      style={{
        width: 650,
        height: 1240,
        borderRadius: 92,
        padding: 18,
        background: 'linear-gradient(150deg, #858BAA, #20243A 30%, #050712 70%)',
        boxShadow: '0 80px 160px rgba(0,0,0,.55), 0 0 90px rgba(155,123,255,.2)',
        rotate: `${interpolate(frame, [0, 100, 239], [-5, 2, -1])}deg`,
        translate: `0 ${interpolate(frame, [0, 24], [120, 0], {
          extrapolateRight: 'clamp',
          easing: Easing.bezier(0.16, 1, 0.3, 1),
        })}px`,
        scale: interpolate(frame, [0, 24], [0.86, 1], {
          extrapolateRight: 'clamp',
          easing: Easing.bezier(0.16, 1, 0.3, 1),
        }),
      }}
    >
      <div
        style={{
          width: '100%',
          height: '100%',
          borderRadius: 76,
          overflow: 'hidden',
          background:
            'radial-gradient(circle at 35% 28%, #463A91, transparent 38%), radial-gradient(circle at 70% 76%, #116172, transparent 42%), #0B0E22',
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          justifyContent: 'center',
          gap: 38,
          color: colors.ink,
          fontFamily: 'Arial, Helvetica, sans-serif',
        }}
      >
        <div
          style={{
            position: 'absolute',
            top: 42,
            width: 210,
            height: 54,
            borderRadius: 28,
            background: '#02030A',
          }}
        />
        <div
          style={{
            width: 164,
            height: 164,
            borderRadius: 46,
            background: `linear-gradient(135deg, ${colors.violet}, ${colors.cyan})`,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            fontSize: 84,
            fontWeight: 800,
            boxShadow: '0 34px 70px rgba(94,235,255,.24)',
            scale: interpolate(frame, [18, 42], [0.75, 1], {
              extrapolateLeft: 'clamp',
              extrapolateRight: 'clamp',
              easing: Easing.bezier(0.16, 1, 0.3, 1),
            }),
          }}
        >
          H
        </div>
        <div style={{fontSize: 72, fontWeight: 760, letterSpacing: -3}}>Hello, world.</div>
        <div style={{fontSize: 34, color: colors.muted}}>Your first idea starts here.</div>
        <div
          style={{
            marginTop: 36,
            padding: '28px 58px',
            borderRadius: 44,
            background: colors.ink,
            color: colors.dark,
            fontSize: 30,
            fontWeight: 800,
          }}
        >
          Get started
        </div>
      </div>
    </div>
  );
};

const CopyScene: React.FC<{
  start: number;
  end: number;
  eyebrow: string;
  headline: string;
  accent: string;
}> = ({start, end, eyebrow, headline, accent}) => {
  const frame = useCurrentFrame();
  return (
    <div
      style={{
        opacity: fade(frame, start, end),
        display: 'flex',
        flexDirection: 'column',
        gap: 30,
        width: 900,
        translate: `0 ${interpolate(frame, [start, start + 18], [70, 0], {
          extrapolateLeft: 'clamp',
          extrapolateRight: 'clamp',
          easing: Easing.bezier(0.16, 1, 0.3, 1),
        })}px`,
      }}
    >
      <div
        style={{
          color: accent,
          fontSize: 34,
          fontWeight: 800,
          textTransform: 'uppercase',
          letterSpacing: 7,
        }}
      >
        {eyebrow}
      </div>
      <div style={{fontSize: 112, lineHeight: 0.98, fontWeight: 780, letterSpacing: -6}}>
        {headline}
      </div>
    </div>
  );
};

const Video: React.FC = () => {
  const frame = useCurrentFrame();
  return (
    <AbsoluteFill style={{fontFamily: 'Arial, Helvetica, sans-serif', color: colors.ink}}>
      <Background />
      <AbsoluteFill style={{padding: '100px 84px 110px'}}>
        <div style={{opacity: interpolate(frame, [0, 16], [0, 1], {extrapolateRight: 'clamp'})}}>
          <Wordmark />
        </div>
        <div
          style={{
            flex: 1,
            display: 'flex',
            flexDirection: 'column',
            justifyContent: 'center',
            alignItems: 'center',
            gap: 72,
          }}
        >
          <CopyScene
            start={0}
            end={82}
            eyebrow="Meet Hello"
            headline={'One small greeting.\nA world of possibility.'}
            accent={colors.cyan}
          />
          <Sequence from={16} durationInFrames={184} layout="none">
            <Phone />
          </Sequence>
          <CopyScene
            start={104}
            end={180}
            eyebrow="Made to begin"
            headline={'Simple by design.\nReady for your ideas.'}
            accent={colors.violet}
          />
        </div>
        <div
          style={{
            opacity: fade(frame, 182, 239),
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            gap: 24,
            textAlign: 'center',
          }}
        >
          <div style={{fontSize: 94, fontWeight: 800, letterSpacing: -5}}>Say hello.</div>
          <div style={{fontSize: 38, color: colors.muted}}>Available on the App Store</div>
        </div>
      </AbsoluteFill>
    </AbsoluteFill>
  );
};

export const RemotionRoot: React.FC = () => (
  <>
    <Composition
      id="HelloWorldPreview"
      component={Video}
      durationInFrames={240}
      fps={30}
      width={1080}
      height={1920}
      defaultProps={{}}
    />
    <Composition
      id="BeyondFrenchPreview"
      component={BeyondFrenchVideo}
      durationInFrames={300}
      fps={30}
      width={1080}
      height={1920}
      defaultProps={defaultBeyondFrenchVideoProps}
    />
  </>
);
