import type {DailyBreathStoryProps} from './DailyBreathStory';

// Validate the final video copy, regardless of whether it came from the admin,
// the Windows worker, or a manually supplied Remotion props file.
export const validateDailyBreathStoryProps = (props: DailyBreathStoryProps): void => {
  const fields: Array<[string, string]> = [
    ['title', props.title],
    ['subtitle', props.subtitle],
    ['outro', props.outroText],
    ...props.beats.flatMap((beat) => [
      [`${beat.id} label`, beat.label],
      [`${beat.id} screen text`, beat.onScreenText],
      [`${beat.id} narration`, beat.narration],
    ] as Array<[string, string]>),
  ];
  for (const [name, value] of fields) {
    if (typeof value !== 'string' || !value.trim()) {
      throw new Error(`Daily Breath video: ${name} is empty.`);
    }
    if (/\uFFFD|[\u0000-\u0008\u000B\u000C\u000E-\u001F]/u.test(value)) {
      throw new Error(`Daily Breath video: ${name} contains a broken character or control code.`);
    }
    // These sequences are copy/formatting artifacts, not ordinary scripture
    // punctuation. Preserve normal punctuation, quotations, and RTL marks.
    if (/(?:[,;:!?]\s*){2,}|(?:\*{2,}|_{2,}|#{2,}|`{2,})/u.test(value)) {
      throw new Error(`Daily Breath video: review punctuation in ${name}: ${value.slice(0, 100)}`);
    }
  }
};
