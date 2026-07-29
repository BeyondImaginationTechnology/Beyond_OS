import React from 'react';
import {Audio} from '@remotion/media';
import {
  AbsoluteFill,
  Easing,
  Img,
  Sequence,
  interpolate,
  staticFile,
  useCurrentFrame,
  useVideoConfig,
} from 'remotion';

export type DailyStencilProps = {
  mainArtwork: string;
  studioTransfer: string;
  collectionName: string;
  stencilTitle: string;
  date: string;
  suggestedPlacement: string;
  downloadUrl: string;
  caption?: string;
  style?: string;
  audioFile?: string;
  showQrCode?: boolean;
  qrDataUrl?: string;
};

export const defaultDailyStencilProps: DailyStencilProps = {
  mainArtwork: 'stencils/main-stencil.webp',
  studioTransfer: 'stencils/studio-transfer.png',
  collectionName: 'Beyond Ancient Collection',
  stencilTitle: 'Eye of Horus Anubis',
  date: 'Tuesday, July 21, 2026',
  suggestedPlacement: 'Outer forearm · 6.5–8.5 inches tall',
  downloadUrl:
    'https://beyondimagination.co.technology/beyond-tattoo/stencil-of-day.php',
  caption:
    'Premium Egyptian-inspired realism with clean, transfer-ready line work.',
  style: 'Engraving realism',
  audioFile: '',
  showQrCode: true,
  qrDataUrl: '',
};

const C = {
  ink: '#08090B',
  paper: '#F3EFE7',
  gold: '#D8AB52',
  warm: '#8F2F2A',
  smoke: '#A8A29A',
  white: '#FFFDF7',
};

const clamp = {
  extrapolateLeft: 'clamp' as const,
  extrapolateRight: 'clamp' as const,
};

const fadeWindow = (
  frame: number,
  start: number,
  end: number,
  edge = 22,
) =>
  interpolate(
    frame,
    [start, start + edge, end - edge, end],
    [0, 1, 1, 0],
    clamp,
  );

const Grain: React.FC = () => (
  <AbsoluteFill
    style={{
      opacity: 0.2,
      backgroundImage:
        'radial-gradient(rgba(255,255,255,.22) .7px, transparent .7px)',
      backgroundSize: '11px 11px',
      mixBlendMode: 'soft-light',
    }}
  />
);

const Brand: React.FC = () => {
  const frame = useCurrentFrame();
  return (
    <div
      style={{
        display: 'flex',
        alignItems: 'center',
        gap: 18,
        opacity: interpolate(frame, [0, 25], [0, 1], clamp),
        translate: `0 ${interpolate(frame, [0, 35], [24, 0], {
          ...clamp,
          easing: Easing.bezier(0.16, 1, 0.3, 1),
        })}px`,
      }}
    >
      <Img
        src={staticFile('brand/beyond-tattoo-logo.webp')}
        style={{width: 62, height: 62, borderRadius: 18, objectFit: 'cover'}}
      />
      <div>
        <div
          style={{
            fontSize: 27,
            fontWeight: 900,
            letterSpacing: 5,
            textTransform: 'uppercase',
          }}
        >
          Beyond Tattoo
        </div>
        <div
          style={{
            marginTop: 4,
            color: C.gold,
            fontSize: 15,
            fontWeight: 850,
            letterSpacing: 4,
            textTransform: 'uppercase',
          }}
        >
          Stencil of the day
        </div>
      </div>
    </div>
  );
};

const ArtworkFrame: React.FC<{
  src: string;
  mirrored?: boolean;
  label: string;
  start: number;
  end: number;
}> = ({src, mirrored = false, label, start, end}) => {
  const frame = useCurrentFrame();
  return (
    <div
      style={{
        position: 'absolute',
        left: 70,
        right: 70,
        top: 250,
        bottom: 250,
        overflow: 'hidden',
        borderRadius: 46,
        background: C.paper,
        border: '1px solid rgba(216,171,82,.52)',
        boxShadow:
          '0 60px 150px rgba(0,0,0,.58), inset 0 0 0 12px rgba(8,9,11,.04)',
        opacity: fadeWindow(frame, start, end),
        scale: interpolate(frame, [start, end], [1.08, 1.01], clamp),
        rotate: `${interpolate(frame, [start, end], [-1.2, 0.8], clamp)}deg`,
      }}
    >
      <Img
        src={staticFile(src)}
        style={{
          width: '100%',
          height: '100%',
          objectFit: 'contain',
          padding: 42,
          filter: 'contrast(1.08)',
          scale: mirrored ? '-1 1' : '1',
        }}
      />
      <div
        style={{
          position: 'absolute',
          inset: 0,
          boxShadow: 'inset 0 -180px 160px rgba(8,9,11,.12)',
          pointerEvents: 'none',
        }}
      />
      <div
        style={{
          position: 'absolute',
          left: 26,
          bottom: 26,
          padding: '15px 23px',
          borderRadius: 999,
          color: C.white,
          background: 'rgba(8,9,11,.9)',
          fontSize: 18,
          fontWeight: 850,
          letterSpacing: 3,
          textTransform: 'uppercase',
        }}
      >
        {label}
      </div>
    </div>
  );
};

const Intro: React.FC<DailyStencilProps> = ({
  stencilTitle,
  collectionName,
  date,
  mainArtwork,
}) => {
  const frame = useCurrentFrame();
  return (
    <AbsoluteFill>
      <ArtworkFrame
        src={mainArtwork}
        label="Original stencil master"
        start={25}
        end={245}
      />
      <div
        style={{
          position: 'absolute',
          left: 84,
          right: 84,
          bottom: 84,
          opacity: interpolate(frame, [35, 70], [0, 1], clamp),
          translate: `0 ${interpolate(frame, [35, 78], [52, 0], {
            ...clamp,
            easing: Easing.bezier(0.16, 1, 0.3, 1),
          })}px`,
        }}
      >
        <div
          style={{
            color: C.gold,
            fontSize: 23,
            fontWeight: 900,
            letterSpacing: 5,
            textTransform: 'uppercase',
          }}
        >
          {collectionName}
        </div>
        <div
          style={{
            marginTop: 13,
            color: C.white,
            fontFamily: 'Georgia, Times New Roman, serif',
            fontSize: 82,
            fontWeight: 700,
            lineHeight: 0.98,
            letterSpacing: -3,
          }}
        >
          {stencilTitle}
        </div>
        <div style={{marginTop: 20, color: C.smoke, fontSize: 24}}>{date}</div>
      </div>
    </AbsoluteFill>
  );
};

const TransferScene: React.FC<DailyStencilProps> = ({
  studioTransfer,
  style,
  suggestedPlacement,
}) => {
  const frame = useCurrentFrame();
  return (
    <AbsoluteFill>
      <ArtworkFrame
        src={studioTransfer}
        mirrored
        label="Mirrored transfer view"
        start={0}
        end={240}
      />
      <div
        style={{
          position: 'absolute',
          left: 84,
          right: 84,
          bottom: 80,
          display: 'flex',
          gap: 16,
          opacity: interpolate(frame, [18, 48], [0, 1], clamp),
        }}
      >
        {[style || 'Artist-ready linework', suggestedPlacement].map(
          (item, index) => (
            <div
              key={item}
              style={{
                flex: 1,
                minHeight: 100,
                padding: '20px 22px',
                borderRadius: 24,
                background: index === 0 ? C.gold : C.warm,
                color: index === 0 ? C.ink : C.white,
                fontSize: 20,
                fontWeight: 850,
                lineHeight: 1.25,
                translate: `0 ${interpolate(
                  frame,
                  [22 + index * 8, 58 + index * 8],
                  [32, 0],
                  clamp,
                )}px`,
              }}
            >
              <div
                style={{
                  marginBottom: 7,
                  fontSize: 13,
                  letterSpacing: 3,
                  textTransform: 'uppercase',
                  opacity: 0.7,
                }}
              >
                {index === 0 ? 'Style' : 'Placement'}
              </div>
              {item}
            </div>
          ),
        )}
      </div>
    </AbsoluteFill>
  );
};

const EndScene: React.FC<DailyStencilProps> = ({
  stencilTitle,
  collectionName,
  caption,
  downloadUrl,
  qrDataUrl,
  mainArtwork,
}) => {
  const frame = useCurrentFrame();
  return (
    <AbsoluteFill
      style={{
        padding: '210px 76px 100px',
        display: 'flex',
        flexDirection: 'column',
        justifyContent: 'center',
        alignItems: 'center',
        textAlign: 'center',
        opacity: interpolate(frame, [0, 30], [0, 1], clamp),
      }}
    >
      <div
        style={{
          width: 350,
          height: 500,
          padding: 18,
          overflow: 'hidden',
          borderRadius: 34,
          background: C.paper,
          border: '1px solid rgba(216,171,82,.6)',
          boxShadow: '0 35px 90px rgba(0,0,0,.46)',
          translate: `0 ${interpolate(frame, [0, 42], [48, 0], {
            ...clamp,
            easing: Easing.bezier(0.16, 1, 0.3, 1),
          })}px`,
        }}
      >
        <Img
          src={staticFile(mainArtwork)}
          style={{width: '100%', height: '100%', objectFit: 'contain'}}
        />
      </div>
      <div
        style={{
          marginTop: 38,
          color: C.gold,
          fontSize: 19,
          fontWeight: 900,
          letterSpacing: 5,
          textTransform: 'uppercase',
        }}
      >
        {collectionName}
      </div>
      <div
        style={{
          marginTop: 13,
          color: C.white,
          fontFamily: 'Georgia, Times New Roman, serif',
          fontSize: 68,
          fontWeight: 700,
          lineHeight: 1,
        }}
      >
        {stencilTitle}
      </div>
      {caption ? (
        <div
          style={{
            maxWidth: 810,
            marginTop: 20,
            color: C.smoke,
            fontSize: 23,
            lineHeight: 1.35,
          }}
        >
          {caption}
        </div>
      ) : null}
      <div
        style={{
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          gap: 24,
          marginTop: 34,
        }}
      >
        {qrDataUrl ? (
          <Img
            src={qrDataUrl}
            style={{
              width: 152,
              height: 152,
              padding: 9,
              borderRadius: 18,
              background: C.white,
            }}
          />
        ) : null}
        <div style={{textAlign: 'left'}}>
          <div
            style={{
              display: 'inline-block',
              padding: '20px 27px',
              borderRadius: 16,
              background: C.gold,
              color: C.ink,
              fontSize: 25,
              fontWeight: 950,
              letterSpacing: 1,
            }}
          >
            DOWNLOAD THE PACK
          </div>
          <div
            style={{
              maxWidth: 510,
              marginTop: 13,
              color: C.smoke,
              fontSize: 16,
              overflowWrap: 'anywhere',
            }}
          >
            {downloadUrl}
          </div>
        </div>
      </div>
    </AbsoluteFill>
  );
};

export const DailyStencilPack: React.FC<DailyStencilProps> = (props) => {
  const frame = useCurrentFrame();
  const {durationInFrames} = useVideoConfig();
  return (
    <AbsoluteFill
      style={{
        overflow: 'hidden',
        color: C.white,
        fontFamily: 'Arial, Helvetica, sans-serif',
        background:
          'radial-gradient(circle at 18% 20%, rgba(143,47,42,.25), transparent 34%), radial-gradient(circle at 86% 76%, rgba(216,171,82,.16), transparent 37%), #08090B',
      }}
    >
      <Grain />
      <div style={{position: 'absolute', left: 70, top: 76, zIndex: 20}}>
        <Brand />
      </div>
      <Sequence from={0} durationInFrames={250}>
        <Intro {...props} />
      </Sequence>
      <Sequence from={220} durationInFrames={250}>
        <TransferScene {...props} />
      </Sequence>
      <Sequence from={440} durationInFrames={160}>
        <EndScene {...props} />
      </Sequence>
      <div
        style={{
          position: 'absolute',
          left: 0,
          right: 0,
          bottom: 0,
          height: 8,
          background: 'rgba(255,255,255,.1)',
        }}
      >
        <div
          style={{
            width: `${interpolate(
              frame,
              [0, durationInFrames - 1],
              [0, 100],
              clamp,
            )}%`,
            height: '100%',
            background: C.gold,
          }}
        />
      </div>
      {props.audioFile ? (
        <Audio src={staticFile(props.audioFile)} volume={0.95} />
      ) : null}
    </AbsoluteFill>
  );
};
