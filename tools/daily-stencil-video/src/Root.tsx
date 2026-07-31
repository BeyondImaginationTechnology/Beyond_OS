import React from 'react';
import {CalculateMetadataFunction, Composition} from 'remotion';
import QRCode from 'qrcode';
import {DailyStencilPack, DailyStencilProps, defaultDailyStencilProps} from './DailyStencilPack';

const calculateMetadata: CalculateMetadataFunction<DailyStencilProps> = async ({props}) => {
  const qrDataUrl = props.showQrCode && props.downloadUrl
    ? await QRCode.toDataURL(props.downloadUrl, {margin: 1, width: 320})
    : '';
  return {durationInFrames: 600, fps: 60, width: 1080, height: 1080, props: {...props, qrDataUrl}};
};

export const RemotionRoot: React.FC = () => (
  <Composition id="DailyStencilPack" component={DailyStencilPack} durationInFrames={600} fps={60} width={1080} height={1080} defaultProps={defaultDailyStencilProps} calculateMetadata={calculateMetadata}/>
);
