import bpy, os
ROOT=r'C:\\Users\\Greg\\Documents\\Beyond_OS\\dailybreath\\assets\\videos'
SRC=os.path.join(ROOT,'DB_Christian_StudyV18_Chris_Morning_Verse_Test.blend')
bpy.ops.wm.open_mainfile(filepath=SRC)
s=bpy.context.scene
s.frame_start=1;s.frame_end=48;s.render.fps=12;s.render.resolution_x=640;s.render.resolution_y=360;s.render.resolution_percentage=100
s.render.image_settings.file_format='PNG';s.render.filepath=os.path.join(ROOT,'renders','test_v18_frames','frame_')
os.makedirs(os.path.dirname(s.render.filepath),exist_ok=True)
s.timeline_markers.clear()
for name,frame,cam in [('Test opener',1,'Chris_Morning_Wide_Camera'),('Test presenter',25,'Chris_Morning_Close_Camera')]:
 m=s.timeline_markers.new(name,frame=frame);m.camera=bpy.data.objects[cam]
s.camera=bpy.data.objects['Chris_Morning_Wide_Camera']
bpy.ops.render.render(animation=True)
