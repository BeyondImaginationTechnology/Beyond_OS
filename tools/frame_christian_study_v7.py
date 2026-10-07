import bpy
from mathutils import Vector
cam=bpy.data.objects.get('Chris_Presenter_Camera') or bpy.context.scene.camera
cam.location=(-0.1,-5.4,2.25)
target=Vector((-0.1,1.25,1.05))
cam.rotation_euler=((target-cam.location).to_track_quat('-Z','Y')).to_euler()
cam.data.lens=52
bpy.context.scene.camera=cam
bpy.ops.wm.save_as_mainfile(filepath=r"C:\Users\Greg\Documents\Beyond_OS\dailybreath\assets\videos\DB_Christian_StudyV7_Chris_Rigged.blend")
