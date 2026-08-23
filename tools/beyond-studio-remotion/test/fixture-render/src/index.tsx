import React from 'react';
import {AbsoluteFill, Composition, interpolate, registerRoot, useCurrentFrame} from 'remotion';

const SmokeVideo = () => {
  const frame = useCurrentFrame();
  const opacity = interpolate(frame, [0, 8], [0, 1], {extrapolateRight: 'clamp'});
  return (
    <AbsoluteFill style={{alignItems: 'center', backgroundColor: '#09080e', color: '#fff', display: 'flex', fontFamily: 'sans-serif', justifyContent: 'center'}}>
      <div style={{fontSize: 34, fontWeight: 800, opacity}}>Beyond Remotion MP4</div>
    </AbsoluteFill>
  );
};

const Root = () => <Composition id="SmokeVideo" component={SmokeVideo} durationInFrames={30} fps={30} width={320} height={320} />;

registerRoot(Root);
