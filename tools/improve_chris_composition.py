import bpy, os
from mathutils import Vector
ROOT=r'C:\\Users\\Greg\\Documents\\Beyond_OS\\dailybreath\\assets\\videos'
SRC=os.path.join(ROOT,'DB_Christian_StudyV16_Chris_Morning_Verse_Test.blend')
OUT=os.path.join(ROOT,'DB_Christian_StudyV17_Chris_Morning_Verse_Test.blend')
bpy.ops.wm.open_mainfile(filepath=SRC)
# Use real line breaks and keep every word inside the framed verse board.
p=bpy.data.objects['DB_Study_Verse_Passage']
p.data.body="You will keep whoever’s mind\\nsteadfast in perfect peace,\\nbecause he trusts in you."
p.data.size=.105
p.data.align_x='CENTER';p.data.align_y='CENTER'
p.location=(2.25,2.68,2.00)
# Compose Chris and the board together without cutting the board off.
cam=bpy.data.objects['Chris_Morning_Wide_Camera']
cam.location=(0.70,-6.4,2.15)
cam.data.lens=44
cam.rotation_euler=(Vector((1.0,2.35,1.35))-cam.location).to_track_quat('-Z','Y').to_euler()
# Board reference stays low and title stays high; passage sits between them.
bpy.context.scene.render.image_settings.file_format='PNG'
bpy.context.scene.render.resolution_x=1280;bpy.context.scene.render.resolution_y=720
bpy.ops.wm.save_as_mainfile(filepath=OUT)
print('SAVED',OUT)
