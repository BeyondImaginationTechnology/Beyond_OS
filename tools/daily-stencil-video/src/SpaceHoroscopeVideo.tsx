import React from 'react';
import {Audio} from '@remotion/media';
import {
  AbsoluteFill,
  Img,
  staticFile,
} from 'remotion';

export type SpaceHoroscopeProps = {
  sign: string;
  symbol: string;
  season: string;
  date: string;
  headline: string;
  paragraphs: string[];
  mood: string;
  backgroundImage: string;
  audioFile?: string;
  source?: string;
};

export const defaultSpaceHoroscopeProps: SpaceHoroscopeProps = {
  sign: 'Aries',
  symbol: '♈',
  season: 'Mar 21 - Apr 19',
  date: 'Wednesday, August 5, 2026',
  headline: '',
  paragraphs: [],
  mood: '',
  backgroundImage: 'space/aries.jpg',
  audioFile: '',
  source: '',
};

const mediaSource = (source = '') =>
  /^(blob:|data:|https?:\/\/|\/)/i.test(source) ? source : staticFile(source);

export const SpaceHoroscopeVideo: React.FC<SpaceHoroscopeProps> = (props) => {
  return (
    <AbsoluteFill
      style={{
        overflow: 'hidden',
        color: '#FFF7DF',
        background: '#030302',
        fontFamily: 'Arial, Helvetica, sans-serif',
      }}
    >
      {props.backgroundImage ? (
        <Img
          src={mediaSource(props.backgroundImage)}
          style={{
            position: 'absolute',
            inset: 0,
            width: '100%',
            height: '100%',
            objectFit: 'cover',
          }}
        />
      ) : null}
      {props.audioFile ? <Audio src={mediaSource(props.audioFile)} volume={0.96} /> : null}
    </AbsoluteFill>
  );
};
