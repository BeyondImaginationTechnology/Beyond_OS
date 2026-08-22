import React from 'react';
import {Composition} from 'remotion';
import {AfricanExpansion} from './AfricanExpansion';

export const RemotionRoot: React.FC = () => {
  return (
    <>
      <Composition
        id="AfricanExpansion"
        component={AfricanExpansion}
        durationInFrames={360}
        fps={30}
        width={1080}
        height={1920}
        defaultProps={{}}
      />
      <Composition
        id="AfricanExpansionFeed"
        component={AfricanExpansion}
        durationInFrames={360}
        fps={30}
        width={1080}
        height={1350}
        defaultProps={{ isFeed: true } as any}
      />
    </>
  );
};
