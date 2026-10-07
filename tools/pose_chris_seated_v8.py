import bpy
from math import radians
out=r"C:\Users\Greg\Documents\Beyond_OS\dailybreath\assets\videos\DB_Christian_StudyV8_Chris_Seated.blend"
arm=bpy.data.objects['Chris_Rig']
# Lower Chris behind the desk to the chair-seat height.
arm.location.z=0.28
arm.rotation_mode='XYZ'
# Mixamo seated pose: thighs forward, calves down.  Hands rest near the desk.
for name, x in {
 'mixamorig:LeftUpLeg': -85, 'mixamorig:RightUpLeg': -85,
 'mixamorig:LeftLeg': 93, 'mixamorig:RightLeg': 93,
 'mixamorig:LeftFoot': -8, 'mixamorig:RightFoot': -8,
 'mixamorig:LeftArm': -8, 'mixamorig:RightArm': -8,
 'mixamorig:LeftForeArm': -42, 'mixamorig:RightForeArm': -42,
}.items():
 b=arm.pose.bones.get(name)
 if b:
  b.rotation_mode='XYZ'
  b.rotation_euler.x=radians(x)
# Tiny forward posture for a presenter.
for name,x in [('mixamorig:Hips',4),('mixamorig:Spine',-4),('mixamorig:Spine1',-3)]:
 b=arm.pose.bones.get(name)
 if b:
  b.rotation_mode='XYZ'; b.rotation_euler.x=radians(x)
arm['pose'] = 'seated_presenter'
bpy.context.view_layer.objects.active=arm
bpy.ops.wm.save_as_mainfile(filepath=out)
