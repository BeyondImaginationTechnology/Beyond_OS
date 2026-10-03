import React from 'react';
import {CalculateMetadataFunction, Composition} from 'remotion';
import QRCode from 'qrcode';
import {DailyStencilPack, DailyStencilProps, defaultDailyStencilProps} from './DailyStencilPack';
import {SpaceHoroscopeProps, SpaceHoroscopeVideo, defaultSpaceHoroscopeProps} from './SpaceHoroscopeVideo';
import {
  DailyBreathVideo,
  DailyBreathVideoProps,
  defaultDailyBreathVideoProps,
} from './DailyBreathVideo';
import {
  DailyBreathStory,
  DailyBreathStoryProps,
  defaultDailyBreathStoryProps,
} from './DailyBreathStory';

const calculateMetadata: CalculateMetadataFunction<DailyStencilProps> = async ({props}) => {
  const qrDataUrl = props.showQrCode && props.downloadUrl
    ? await QRCode.toDataURL(props.downloadUrl, {margin: 1, width: 320})
    : '';
  return {durationInFrames: 600, fps: 60, width: 1080, height: 1080, props: {...props, qrDataUrl}};
};

const calculateDailyBreathMetadata: CalculateMetadataFunction<
  DailyBreathVideoProps
> = ({props}) => {
  const phaseDuration = props.phases.reduce(
    (total, phase) => total + phase.durationSeconds,
    0,
  );
  const durationInFrames = Math.round(
    (props.introSeconds + phaseDuration * props.cycleCount + props.outroSeconds) *
      props.fps,
  );
  return {
    durationInFrames,
    fps: props.fps,
    width: props.width,
    height: props.height,
  };
};

export const RemotionRoot: React.FC = () => (
  <>
    <Composition
      id="DailyBreathStory"
      component={DailyBreathStory}
      durationInFrames={1800}
      fps={30}
      width={1080}
      height={1920}
      defaultProps={defaultDailyBreathStoryProps}
    />
    <Composition id="DailyStencilPack" component={DailyStencilPack} durationInFrames={600} fps={60} width={1080} height={1080} defaultProps={defaultDailyStencilProps} calculateMetadata={calculateMetadata}/>
    <Composition id="SpaceHoroscopeVideo" component={SpaceHoroscopeVideo} durationInFrames={900} fps={30} width={1080} height={1080} defaultProps={defaultSpaceHoroscopeProps as SpaceHoroscopeProps}/>
    <Composition
      id="DailyBreathVideo"
      component={DailyBreathVideo}
      durationInFrames={2010}
      fps={30}
      width={1080}
      height={1920}
      defaultProps={defaultDailyBreathVideoProps}
      calculateMetadata={calculateDailyBreathMetadata}
    />
  </>
);
