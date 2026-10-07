import bpy, os
ROOT=r'C:\\Users\\Greg\\Documents\\Beyond_OS\\dailybreath\\assets\\videos'
SRC=os.path.join(ROOT,'DB_Christian_StudyV17_Chris_Morning_Verse_Test.blend')
OUT=os.path.join(ROOT,'DB_Christian_StudyV18_Chris_Morning_Verse_Test.blend')
bpy.ops.wm.open_mainfile(filepath=SRC)
p=bpy.data.objects['DB_Study_Verse_Passage']
p.data.body = 'You will keep whoever’s mind\nsteadfast in perfect peace,\nbecause he trusts in you.'
p.data.size=.105
# Make opener brief enough for a fast test pass and preserve later narration cut at frame 97.
s=bpy.context.scene
s.frame_start=1;s.frame_end=120;s.render.resolution_x=640;s.render.resolution_y=360;s.render.resolution_percentage=100
s.render.image_settings.file_format='PNG';s.render.filepath=os.path.join(ROOT,'renders','test_frames','frame_')
# Eevee preview settings
s.render.engine='BLENDER_EEVEE'
bpy.ops.wm.save_as_mainfile(filepath=OUT)
print('SAVED',OUT)
