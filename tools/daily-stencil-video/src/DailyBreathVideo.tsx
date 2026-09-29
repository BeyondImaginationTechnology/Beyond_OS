import React from 'react';
import {
  AbsoluteFill,
  Easing,
  interpolate,
  useCurrentFrame,
} from 'remotion';
import config from '../public/daily-breath-video.json';

export type DailyBreathPhase = {
  label: string;
  durationSeconds: number;
  fromScale: number;
  toScale: number;
};

export type DailyBreathVideoProps = {
  brand: string;
  title: string;
  subtitle: string;
  opening: string;
  closing: string;
  safetyNote: string;
  date: string;
  timezone: string;
  introSeconds: number;
  outroSeconds: number;
  cycleCount: number;
  fps: number;
  width: number;
  height: number;
  phases: DailyBreathPhase[];
  palette: {
    background: string;
    foreground: string;
    accent: string;
    muted: string;
  };
};

export const defaultDailyBreathVideoProps =
  config as DailyBreathVideoProps;

const clamp = {
  extrapolateLeft: 'clamp' as const,
  extrapolateRight: 'clamp' as const,
};

export const DailyBreathVideo: React.FC<DailyBreathVideoProps> = (props) => {
  const frame = useCurrentFrame();
  const introFrames = props.introSeconds * props.fps;
  const cycleFrames =
    props.phases.reduce((total, phase) => total + phase.durationSeconds, 0) *
    props.fps;
  const breathingFrame = frame - introFrames;
  const isOpening = frame < introFrames;
  const isClosing = breathingFrame >= cycleFrames * props.cycleCount;
  const isBreathing = !isOpening && !isClosing;
  const practiceFrame = Math.max(
    0,
    Math.min(breathingFrame, cycleFrames * props.cycleCount - 1),
  );
  const round = isBreathing
    ? Math.floor(practiceFrame / cycleFrames) + 1
    : props.cycleCount;
  const frameInCycle = practiceFrame % cycleFrames;

  let phaseFrame = frameInCycle;
  let phase = props.phases[props.phases.length - 1];
  for (const candidate of props.phases) {
    const duration = candidate.durationSeconds * props.fps;
    if (phaseFrame < duration) {
      phase = candidate;
      break;
    }
    phaseFrame -= duration;
  }

  const phaseDuration = phase.durationSeconds * props.fps;
  const progress = phaseDuration <= 1 ? 1 : phaseFrame / (phaseDuration - 1);
  const breathScale = isBreathing
    ? interpolate(progress, [0, 1], [phase.fromScale, phase.toScale], {
        ...clamp,
        easing: Easing.inOut(Easing.sin),
      })
    : isOpening
      ? 0.92
      : 1;
  const countdown = Math.max(
    1,
    Math.ceil((phaseDuration - phaseFrame) / props.fps),
  );
  const palette = props.palette;

  return (
    <AbsoluteFill
      style={{
        alignItems: 'center',
        background: `radial-gradient(circle at 50% 43%, ${palette.accent}22, transparent 42%), ${palette.background}`,
        color: palette.foreground,
        fontFamily: 'Arial, Helvetica, sans-serif',
        justifyContent: 'space-between',
        overflow: 'hidden',
        padding: '130px 90px 90px',
        textAlign: 'center',
      }}
    >
      <div>
        <div
          style={{
            color: palette.accent,
            fontSize: 25,
            fontWeight: 800,
            letterSpacing: 8,
            textTransform: 'uppercase',
          }}
        >
          {props.brand}
        </div>
        <div
          style={{
            fontFamily: 'Georgia, Times New Roman, serif',
            fontSize: 68,
            fontWeight: 700,
            lineHeight: 1.08,
            marginTop: 38,
          }}
        >
          {props.title}
        </div>
        <div
          style={{
            color: palette.muted,
            fontSize: 28,
            marginTop: 18,
          }}
        >
          {props.subtitle}
        </div>
      </div>

      <div
        style={{
          alignItems: 'center',
          display: 'flex',
          height: 720,
          justifyContent: 'center',
          position: 'relative',
          width: 720,
        }}
      >
        <div
          style={{
            border: `2px solid ${palette.accent}55`,
            borderRadius: '50%',
            height: 670,
            position: 'absolute',
            width: 670,
          }}
        />
        <div
          style={{
            alignItems: 'center',
            background: `radial-gradient(circle at 35% 30%, ${palette.foreground}22, transparent 60%), ${palette.accent}1c`,
            border: `2px solid ${palette.accent}aa`,
            borderRadius: '50%',
            boxShadow: `0 0 100px ${palette.accent}30`,
            display: 'flex',
            flexDirection: 'column',
            height: 500,
            justifyContent: 'center',
            transform: `scale(${breathScale})`,
            width: 500,
          }}
        >
          <div
            style={{
              color: palette.accent,
              fontSize: 25,
              fontWeight: 800,
              letterSpacing: 5,
              textTransform: 'uppercase',
            }}
          >
            {isOpening ? 'Get comfortable' : isClosing ? 'Practice complete' : phase.label}
          </div>
          <div
            style={{
              fontFamily: 'Georgia, Times New Roman, serif',
              fontSize: 112,
              fontWeight: 700,
              lineHeight: 1,
              marginTop: 25,
            }}
          >
            {isBreathing ? countdown : ''}
          </div>
          <div
            style={{
              color: palette.muted,
              fontSize: 23,
              marginTop: 20,
            }}
          >
            {isOpening
              ? props.opening
              : isClosing
                ? props.closing
                : `${round} / ${props.cycleCount}`}
          </div>
        </div>
      </div>

      <div style={{maxWidth: 860}}>
        <div
          style={{
            color: palette.muted,
            fontSize: 21,
            lineHeight: 1.45,
          }}
        >
          {props.safetyNote}
        </div>
        <div
          style={{
            color: palette.accent,
            fontSize: 18,
            letterSpacing: 2,
            marginTop: 22,
          }}
        >
          {props.date}
        </div>
      </div>

    </AbsoluteFill>
  );
};
