import bpy
from mathutils import Vector
scene=bpy.context.scene
marker=bpy.data.objects.get('DB_Study_Chris_Marker')
if marker: marker.hide_render=True
cam=bpy.data.objects.get('Chris_Presenter_Camera') or scene.camera
cam.location=(-0.1,-4.35,2.05)
target=Vector((-0.1,1.28,1.05))
cam.rotation_euler=((target-cam.location).to_track_quat('-Z','Y')).to_euler()
cam.data.lens=55
scene.camera=cam
bpy.ops.wm.save_as_mainfile(filepath=r"C:\Users\Greg\Documents\Beyond_OS\dailybreath\assets\videos\DB_Christian_StudyV7_Chris_Rigged.blend")
