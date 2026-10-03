import React from 'react';
import {AbsoluteFill, interpolate, Sequence, spring, useCurrentFrame, useVideoConfig} from 'remotion';

export type CosmicReading = {sign: string; symbol: string; mood: string; paragraphs: string[]};
export type SpaceHoroscopeProps = {title: string; date: string; theme: string; readings: CosmicReading[]; source?: string; disclaimer?: string; durationSeconds?: number};

export const defaultSpaceHoroscopeProps: SpaceHoroscopeProps = {
  title: 'Cosmic Compass', date: 'October 3, 2026', theme: 'A little wonder, one grounded step.', readings: [], source: 'Beyond Space original', disclaimer: 'For entertainment and personal reflection.', durationSeconds: 300,
};

const stars = Array.from({length: 88}, (_, index) => ({left: `${(index * 37) % 100}%`, top: `${(index * 61) % 100}%`, size: 1 + ((index * 17) % 5), delay: (index % 12) * 4}));

const Sky: React.FC = () => {
  const frame = useCurrentFrame();
  return <AbsoluteFill style={{background:'radial-gradient(circle at 50% 16%, #293d91 0%, #14183f 38%, #050615 77%)', overflow:'hidden'}}>
    <div style={{position:'absolute', width:1200, height:1200, borderRadius:'50%', border:'2px solid rgba(126,212,255,.12)', left:-320, top:260, transform:`rotate(${frame / 50}deg)`}} />
    <div style={{position:'absolute', width:950, height:950, borderRadius:'50%', border:'1px solid rgba(255,211,123,.16)', right:-260, top:-430, transform:`rotate(${-frame / 75}deg)`}} />
    {stars.map((star, index) => <i key={index} style={{position:'absolute', left:star.left, top:star.top, width:star.size, height:star.size, borderRadius:'50%', background:index % 4 === 0 ? '#ffe3a5' : '#d6f3ff', opacity:.3 + .7 * Math.abs(Math.sin((frame + star.delay) / 26)), boxShadow:`0 0 ${star.size * 4}px currentColor`}} />)}
  </AbsoluteFill>;
};

const Fade: React.FC<{children: React.ReactNode}> = ({children}) => {
  const frame = useCurrentFrame(); const {durationInFrames} = useVideoConfig();
  const opacity = Math.min(interpolate(frame, [0, 18], [0, 1], {extrapolateRight:'clamp'}), interpolate(frame, [durationInFrames - 24, durationInFrames], [1, 0], {extrapolateLeft:'clamp'}));
  return <AbsoluteFill style={{opacity}}>{children}</AbsoluteFill>;
};

const Header: React.FC<{date: string}> = ({date}) => <div style={{position:'absolute', top:70, left:90, right:90, display:'flex', justifyContent:'space-between', fontFamily:'Arial, sans-serif', fontWeight:800, letterSpacing:3, fontSize:22, color:'#b9e8ff'}}><span>BEYOND SPACE TV</span><span>{date.toUpperCase()}</span></div>;

const ReadingScene: React.FC<{reading: CosmicReading; date: string; index: number}> = ({reading, date, index}) => {
  const frame = useCurrentFrame(); const enter = spring({frame, fps:30, config:{damping:120}}); const y = interpolate(enter, [0, 1], [45, 0]);
  return <Fade><Sky/><Header date={date}/><div style={{position:'absolute', inset:'150px 130px 110px', display:'grid', gridTemplateColumns:'360px 1fr', gap:66, alignItems:'center', fontFamily:'Arial, sans-serif'}}>
    <div style={{display:'grid', placeItems:'center', width:330, height:330, borderRadius:'50%', border:'2px solid rgba(189,234,255,.55)', background:'radial-gradient(circle, rgba(111,162,255,.45), rgba(25,29,94,.1) 64%)', boxShadow:'0 0 80px rgba(94,179,255,.28)', transform:`translateY(${y}px)`}}><span style={{fontSize:180, filter:'drop-shadow(0 10px 16px rgba(0,0,0,.32))'}}>{reading.symbol}</span></div>
    <div style={{transform:`translateY(${y}px)`}}><div style={{color:'#ffd782', fontSize:25, letterSpacing:4, fontWeight:800}}>SIGN {String(index + 1).padStart(2, '0')} · {reading.mood.toUpperCase()}</div><h1 style={{fontSize:104, lineHeight:.95, margin:'20px 0 26px', color:'#fff7df'}}>{reading.sign}</h1><p style={{fontSize:40, lineHeight:1.32, margin:0, color:'#e1efff', maxWidth:1000}}>{reading.paragraphs.join(' ')}</p></div>
  </div><div style={{position:'absolute', bottom:52, left:90, color:'#a5b7db', fontSize:20, fontFamily:'Arial, sans-serif'}}>COSMIC COMPASS · DAILY ASTROLOGY · ENTERTAINMENT & REFLECTION</div></Fade>;
};

const TitleScene: React.FC<{props: SpaceHoroscopeProps}> = ({props}) => <Fade><Sky/><div style={{position:'absolute', inset:0, display:'grid', placeItems:'center', textAlign:'center', fontFamily:'Arial, sans-serif'}}><div><div style={{color:'#b9e8ff', fontSize:31, fontWeight:800, letterSpacing:6}}>BEYOND SPACE TV PRESENTS</div><h1 style={{fontSize:124, lineHeight:.88, margin:'34px 0 26px', color:'#fff7df'}}>COSMIC<br/>COMPASS</h1><p style={{fontSize:34, color:'#ffd782', letterSpacing:3, margin:0}}>DAILY ASTROLOGY · {props.date.toUpperCase()}</p></div></div><p style={{position:'absolute', bottom:66, width:'100%', textAlign:'center', fontFamily:'Arial, sans-serif', color:'#b7c6e7', fontSize:21}}>{props.disclaimer}</p></Fade>;
const ThemeScene: React.FC<{props: SpaceHoroscopeProps}> = ({props}) => <Fade><Sky/><Header date={props.date}/><div style={{position:'absolute', inset:'180px 190px', display:'grid', placeItems:'center', textAlign:'center', fontFamily:'Arial, sans-serif'}}><div><p style={{color:'#ffd782', fontSize:28, fontWeight:800, letterSpacing:5}}>TODAY’S COSMIC THEME</p><h2 style={{fontSize:78, lineHeight:1.05, margin:20, color:'#fff7df'}}>{props.theme}</h2></div></div></Fade>;
const CloseScene: React.FC<{props: SpaceHoroscopeProps}> = ({props}) => <Fade><Sky/><div style={{position:'absolute', inset:0, display:'grid', placeItems:'center', textAlign:'center', fontFamily:'Arial, sans-serif'}}><div><p style={{color:'#ffd782', fontSize:29, fontWeight:800, letterSpacing:5}}>UNTIL TOMORROW</p><h2 style={{fontSize:76, lineHeight:1.05, maxWidth:1200, margin:'24px auto', color:'#fff7df'}}>Carry what helps. Leave the rest. Keep your own compass.</h2><p style={{fontSize:28, color:'#c4d8f7'}}>Open Beyond Space for your personal daily reading.</p></div></div><div style={{position:'absolute', bottom:62, left:90, right:90, fontFamily:'Arial, sans-serif', color:'#a5b7db', fontSize:19, display:'flex', justifyContent:'space-between'}}><span>{props.source}</span><span>{props.disclaimer}</span></div></Fade>;

export const SpaceHoroscopeVideo: React.FC<SpaceHoroscopeProps> = (props) => {
  const fps = 30, readingFrames = 18 * fps, introFrames = 18 * fps, themeFrames = 18 * fps, closeFrames = 48 * fps;
  return <AbsoluteFill><Sequence from={0} durationInFrames={introFrames}><TitleScene props={props}/></Sequence><Sequence from={introFrames} durationInFrames={themeFrames}><ThemeScene props={props}/></Sequence>{props.readings.map((reading, index) => <Sequence key={reading.sign} from={introFrames + themeFrames + index * readingFrames} durationInFrames={readingFrames}><ReadingScene reading={reading} date={props.date} index={index}/></Sequence>)}<Sequence from={introFrames + themeFrames + props.readings.length * readingFrames} durationInFrames={closeFrames}><CloseScene props={props}/></Sequence></AbsoluteFill>;
};
